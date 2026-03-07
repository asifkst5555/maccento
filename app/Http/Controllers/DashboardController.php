<?php

namespace App\Http\Controllers;

use App\Mail\BrandedNotificationMail;
use App\Models\AiUsageLog;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\ClientServiceRequest;
use App\Models\EmailLog;
use App\Models\FollowUp;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\PanelNotification;
use App\Models\QuoteEvent;
use App\Models\QuoteBuild;
use App\Models\SendgridWebhookEvent;
use App\Models\User;
use App\Models\WatermarkSetting;
use App\Models\WebsiteFormSubmission;
use App\Services\PanelNotificationService;
use App\Services\QuoteNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Illuminate\View\View;
use ZipArchive;

class DashboardController extends Controller
{
    public function adminDashboard(Request $request): View
    {
        $isOwner = $this->isOwnerRole((string) $request->user()?->role);
        $isManager = $this->isManagerRole((string) $request->user()?->role);
        $canManagePipeline = in_array(strtolower(trim((string) $request->user()?->role)), ['owner', 'admin', 'manager'], true);
        $canViewFinancialWidgets = $isOwner;
        $canViewCostWidgets = $isOwner;
        $canExportData = !$isManager;

        $leadStatus = (string) $request->string('lead_status');
        $leadSearch = trim((string) $request->string('lead_search'));
        $quoteStatus = (string) $request->string('quote_status');
        $quoteSearch = trim((string) $request->string('quote_search'));
        $minTotal = $request->filled('min_total') ? (int) $request->input('min_total') : null;
        $maxTotal = $request->filled('max_total') ? (int) $request->input('max_total') : null;
        [$conversionFromDate, $conversionToDate] = $this->extractDateRange($request, 'conversion_from_date', 'conversion_to_date');
        $conversionFromDate ??= now()->subDays(30)->toDateString();
        $conversionToDate ??= now()->toDateString();
        $dashboardError = null;

        try {
            $stats = [
                'total_users' => User::count(),
                'total_leads' => LeadProfile::count(),
                'qualified_leads' => LeadProfile::whereNotNull('qualified_at')->count(),
                'pending_followups' => FollowUp::where('status', 'pending')->count(),
                'overdue_followups' => FollowUp::where('status', 'pending')->where('due_at', '<', now())->count(),
            ];

            $leadStatusSummary = LeadProfile::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $quoteTotal = QuoteBuild::count();
            $quoteBooked = QuoteBuild::where('status', 'booked')->count();
            $quoteContacted = QuoteBuild::where('status', 'contacted')->count();
            $quoteLost = QuoteBuild::where('status', 'lost')->count();
            $avgQuoteTotal = (int) round((float) QuoteBuild::query()->avg('estimated_total'));
            $conversionRate = $quoteTotal > 0 ? round(($quoteBooked / $quoteTotal) * 100, 1) : 0.0;
            $quoteStatusSummary = QuoteBuild::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $conversionBase = QuoteBuild::query()
            ->when($conversionFromDate !== null, function ($query) use ($conversionFromDate): void {
                $query->where(function ($inner) use ($conversionFromDate): void {
                    $inner->whereDate('submitted_at', '>=', $conversionFromDate)
                        ->orWhere(function ($fallback) use ($conversionFromDate): void {
                            $fallback->whereNull('submitted_at')
                                ->whereDate('created_at', '>=', $conversionFromDate);
                        });
                });
            })
            ->when($conversionToDate !== null, function ($query) use ($conversionToDate): void {
                $query->where(function ($inner) use ($conversionToDate): void {
                    $inner->whereDate('submitted_at', '<=', $conversionToDate)
                        ->orWhere(function ($fallback) use ($conversionToDate): void {
                            $fallback->whereNull('submitted_at')
                                ->whereDate('created_at', '<=', $conversionToDate);
                        });
                });
            });

            $funnelTotal = (clone $conversionBase)->count();
            $funnelReviewed = (clone $conversionBase)->whereIn('status', ['reviewed', 'contacted', 'booked'])->count();
            $funnelContacted = (clone $conversionBase)->whereIn('status', ['contacted', 'booked'])->count();
            $funnelBooked = (clone $conversionBase)->where('status', 'booked')->count();
            $funnelLost = (clone $conversionBase)->where('status', 'lost')->count();
            $funnelAvgTotal = (int) round((float) ((clone $conversionBase)->avg('estimated_total') ?? 0));

            $todayAiUsage = AiUsageLog::query()
                ->whereDate('created_at', now()->toDateString())
                ->selectRaw('count(*) as total_requests, sum(tokens_in + tokens_out) as total_tokens, sum(estimated_cost) as total_cost')
                ->first();

            $leads = LeadProfile::query()
            ->with('conversation:id,status,last_message_at')
            ->when($leadStatus !== '', function ($query) use ($leadStatus): void {
                $query->where('status', $leadStatus);
            })
            ->when($leadSearch !== '', function ($query) use ($leadSearch): void {
                $query->where(function ($inner) use ($leadSearch): void {
                    $inner->where('name', 'like', "%{$leadSearch}%")
                        ->orWhere('email', 'like', "%{$leadSearch}%")
                        ->orWhere('phone', 'like', "%{$leadSearch}%")
                        ->orWhere('service_type', 'like', "%{$leadSearch}%")
                        ->orWhere('location', 'like', "%{$leadSearch}%");
                });
            })
            ->latest('id')
            ->paginate(12, ['*'], 'leads_page')
            ->withQueryString();

            $quotes = QuoteBuild::query()
            ->when($quoteStatus !== '', function ($query) use ($quoteStatus): void {
                $query->where('status', $quoteStatus);
            })
            ->when($quoteSearch !== '', function ($query) use ($quoteSearch): void {
                $query->where(function ($inner) use ($quoteSearch): void {
                    $inner->where('quote_id', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_name', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_email', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_phone', 'like', "%{$quoteSearch}%");
                });
            })
            ->when($minTotal !== null, function ($query) use ($minTotal): void {
                $query->where('estimated_total', '>=', $minTotal);
            })
            ->when($maxTotal !== null, function ($query) use ($maxTotal): void {
                $query->where('estimated_total', '<=', $maxTotal);
            })
            ->latest('id')
            ->paginate(12, ['id', 'quote_id', 'status', 'estimated_total', 'currency', 'submitted_at', 'options'], 'quotes_page')
            ->withQueryString();

            $pendingFollowUps = FollowUp::query()
            ->with(['leadProfile:id,name,email,phone,service_type', 'owner:id,name'])
            ->where('status', 'pending')
            ->orderByRaw('CASE WHEN due_at < ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('due_at')
            ->limit(8)
            ->get();

            $recentSubmissions = WebsiteFormSubmission::query()
            ->latest('submitted_at')
            ->limit(6)
            ->get(['id', 'name', 'email', 'phone', 'service', 'status', 'submitted_at']);

            $trendDates = collect(range(6, 0))
                ->map(static fn (int $offset): string => now()->subDays($offset)->toDateString());

            $leadTrendMap = LeadProfile::query()
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

            $quoteTrendMap = QuoteBuild::query()
            ->selectRaw('date(coalesce(submitted_at, created_at)) as day, count(*) as total')
            ->whereRaw('date(coalesce(submitted_at, created_at)) >= ?', [now()->subDays(6)->toDateString()])
            ->groupBy('day')
            ->pluck('total', 'day');

            $leadTrend = $trendDates
                ->map(static fn (string $day): int => (int) ($leadTrendMap[$day] ?? 0))
                ->values();

            $quoteTrend = $trendDates
                ->map(static fn (string $day): int => (int) ($quoteTrendMap[$day] ?? 0))
                ->values();

            $trendMax = max(
                1,
                (int) max(
                    $leadTrend->max() ?? 0,
                    $quoteTrend->max() ?? 0
                )
            );

            $funnelChart = collect([
                ['key' => 'total', 'label' => 'Quoted', 'value' => (int) $funnelTotal],
                ['key' => 'reviewed', 'label' => 'Reviewed', 'value' => (int) $funnelReviewed],
                ['key' => 'contacted', 'label' => 'Contacted', 'value' => (int) $funnelContacted],
                ['key' => 'booked', 'label' => 'Booked', 'value' => (int) $funnelBooked],
                ['key' => 'lost', 'label' => 'Lost', 'value' => (int) $funnelLost],
            ]);
            $funnelMax = max(1, (int) $funnelChart->max('value'));

            $leadStatusChart = collect($leadStatusSummary)
                ->map(static fn ($count, $status): array => ['status' => (string) $status, 'count' => (int) $count])
                ->sortByDesc('count')
                ->values();
            $leadStatusMax = max(1, (int) $leadStatusChart->max('count'));

            $quoteStatusChart = collect($quoteStatusSummary)
                ->map(static fn ($count, $status): array => ['status' => (string) $status, 'count' => (int) $count])
                ->sortByDesc('count')
                ->values();
            $quoteStatusMax = max(1, (int) $quoteStatusChart->max('count'));

            $trendLabels = $trendDates->map(static fn (string $day): string => Carbon::parse($day)->format('M d'))->values();
            $trendLeadValues = $leadTrend->values();
            $trendQuoteValues = $quoteTrend->values();

            $plotWidth = 360;
            $plotHeight = 120;
            $plotPadding = 8;
            $plotCount = max(1, $trendLeadValues->count());
            $stepX = $plotCount > 1 ? ($plotWidth - ($plotPadding * 2)) / ($plotCount - 1) : 0;
            $toPoints = static function ($series) use ($plotHeight, $plotPadding, $stepX, $trendMax): string {
                return collect($series)->values()->map(function ($value, $index) use ($plotHeight, $plotPadding, $stepX, $trendMax): string {
                    $x = $plotPadding + ($index * $stepX);
                    $y = $plotHeight - $plotPadding - (((int) $value / $trendMax) * ($plotHeight - ($plotPadding * 2)));
                    return round($x, 2) . ',' . round($y, 2);
                })->implode(' ');
            };
            $leadTrendPoints = $toPoints($trendLeadValues);
            $quoteTrendPoints = $toPoints($trendQuoteValues);
        } catch (Throwable $exception) {
            report($exception);
            $dashboardError = 'Dashboard data could not fully load. Please run database migration and cache clear.';

            $stats = [
                'total_users' => 0,
                'total_leads' => 0,
                'qualified_leads' => 0,
                'pending_followups' => 0,
                'overdue_followups' => 0,
            ];
            $leadStatusSummary = collect();
            $quoteStatusSummary = collect();
            $quoteTotal = 0;
            $quoteBooked = 0;
            $quoteContacted = 0;
            $quoteLost = 0;
            $avgQuoteTotal = 0;
            $conversionRate = 0.0;
            $funnelTotal = 0;
            $funnelReviewed = 0;
            $funnelContacted = 0;
            $funnelBooked = 0;
            $funnelLost = 0;
            $funnelAvgTotal = 0;
            $todayAiUsage = (object) ['total_requests' => 0, 'total_tokens' => 0, 'total_cost' => 0];
            $leads = new LengthAwarePaginator([], 0, 12, 1, ['path' => $request->url(), 'pageName' => 'leads_page']);
            $quotes = new LengthAwarePaginator([], 0, 12, 1, ['path' => $request->url(), 'pageName' => 'quotes_page']);
            $pendingFollowUps = collect();
            $recentSubmissions = collect();
            $funnelChart = collect();
            $funnelMax = 1;
            $leadStatusChart = collect();
            $leadStatusMax = 1;
            $quoteStatusChart = collect();
            $quoteStatusMax = 1;
            $trendLabels = collect();
            $trendLeadValues = collect();
            $trendQuoteValues = collect();
            $leadTrendPoints = '';
            $quoteTrendPoints = '';
            $trendMax = 1;
            $trendDates = collect();
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'quoteKpi' => [
                'total' => $quoteTotal,
                'booked' => $quoteBooked,
                'contacted' => $quoteContacted,
                'lost' => $quoteLost,
                'avg_total' => $avgQuoteTotal,
                'conversion_rate' => $conversionRate,
            ],
            'leadStatusSummary' => $leadStatusSummary,
            'quoteStatusSummary' => $quoteStatusSummary,
            'conversionAnalytics' => [
                'from_date' => $conversionFromDate,
                'to_date' => $conversionToDate,
                'total' => $funnelTotal,
                'reviewed' => $funnelReviewed,
                'contacted' => $funnelContacted,
                'booked' => $funnelBooked,
                'lost' => $funnelLost,
                'avg_total' => $funnelAvgTotal,
                'booking_rate' => $funnelTotal > 0 ? round(($funnelBooked / $funnelTotal) * 100, 1) : 0.0,
                'contact_rate' => $funnelTotal > 0 ? round(($funnelContacted / $funnelTotal) * 100, 1) : 0.0,
            ],
            'aiKpi' => [
                'requests_today' => (int) ($todayAiUsage->total_requests ?? 0),
                'tokens_today' => (int) ($todayAiUsage->total_tokens ?? 0),
                'cost_today' => (float) ($todayAiUsage->total_cost ?? 0),
            ],
            'widgetVisibility' => [
                'can_view_financial_widgets' => $canViewFinancialWidgets,
                'can_view_cost_widgets' => $canViewCostWidgets,
                'can_export_data' => $canExportData,
                'can_manage_pipeline' => $canManagePipeline,
                'is_manager' => $isManager,
            ],
            'leads' => $leads,
            'quotes' => $quotes,
            'pendingFollowUps' => $pendingFollowUps,
            'recentSubmissions' => $recentSubmissions,
            'dashboardCharts' => [
                'funnel' => $funnelChart,
                'funnel_max' => $funnelMax,
                'lead_status' => $leadStatusChart,
                'lead_status_max' => $leadStatusMax,
                'quote_status' => $quoteStatusChart,
                'quote_status_max' => $quoteStatusMax,
                'trend' => [
                    'labels' => $trendLabels,
                    'dates' => $trendDates->values(),
                    'lead_values' => $trendLeadValues,
                    'quote_values' => $trendQuoteValues,
                    'lead_points' => $leadTrendPoints,
                    'quote_points' => $quoteTrendPoints,
                    'max' => $trendMax,
                ],
            ],
            'filters' => [
                'lead_status' => $leadStatus,
                'lead_search' => $leadSearch,
                'quote_status' => $quoteStatus,
                'quote_search' => $quoteSearch,
                'min_total' => $minTotal,
                'max_total' => $maxTotal,
                'leads_from_date' => (string) $request->query('leads_from_date', $request->query('from_date', '')),
                'leads_to_date' => (string) $request->query('leads_to_date', $request->query('to_date', '')),
                'quotes_from_date' => (string) $request->query('quotes_from_date', $request->query('from_date', '')),
                'quotes_to_date' => (string) $request->query('quotes_to_date', $request->query('to_date', '')),
                'followups_from_date' => (string) $request->query('followups_from_date', $request->query('from_date', '')),
                'followups_to_date' => (string) $request->query('followups_to_date', $request->query('to_date', '')),
                'conversion_from_date' => $conversionFromDate,
                'conversion_to_date' => $conversionToDate,
            ],
            'dashboardError' => $dashboardError,
        ]);
    }

    public function adminLeadsIndex(Request $request): View
    {
        $leadStatus = (string) $request->string('lead_status');
        $leadSearch = trim((string) $request->string('lead_search'));

        $leads = LeadProfile::query()
            ->with('conversation:id,status,last_message_at')
            ->when($leadStatus !== '', function ($query) use ($leadStatus): void {
                $query->where('status', $leadStatus);
            })
            ->when($leadSearch !== '', function ($query) use ($leadSearch): void {
                $query->where(function ($inner) use ($leadSearch): void {
                    $inner->where('name', 'like', "%{$leadSearch}%")
                        ->orWhere('email', 'like', "%{$leadSearch}%")
                        ->orWhere('phone', 'like', "%{$leadSearch}%")
                        ->orWhere('service_type', 'like', "%{$leadSearch}%")
                        ->orWhere('location', 'like', "%{$leadSearch}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads-index', [
            'leads' => $leads,
            'filters' => [
                'lead_status' => $leadStatus,
                'lead_search' => $leadSearch,
                'leads_from_date' => (string) $request->query('leads_from_date', $request->query('from_date', '')),
                'leads_to_date' => (string) $request->query('leads_to_date', $request->query('to_date', '')),
            ],
            'widgetVisibility' => [
                'can_export_data' => !$this->isManagerRole((string) $request->user()?->role),
            ],
        ]);
    }

    public function adminAiAssistantLeadsIndex(Request $request): View
    {
        $leadStatus = (string) $request->string('lead_status');
        $leadSearch = trim((string) $request->string('lead_search'));

        $leads = LeadProfile::query()
            ->with('conversation:id,channel,status,last_message_at')
            ->whereHas('conversation', function ($query): void {
                $query->where('channel', 'website_widget');
            })
            ->when($leadStatus !== '', function ($query) use ($leadStatus): void {
                $query->where('status', $leadStatus);
            })
            ->when($leadSearch !== '', function ($query) use ($leadSearch): void {
                $query->where(function ($inner) use ($leadSearch): void {
                    $inner->where('name', 'like', "%{$leadSearch}%")
                        ->orWhere('email', 'like', "%{$leadSearch}%")
                        ->orWhere('phone', 'like', "%{$leadSearch}%")
                        ->orWhere('service_type', 'like', "%{$leadSearch}%")
                        ->orWhere('location', 'like', "%{$leadSearch}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ai-leads-index', [
            'leads' => $leads,
            'filters' => [
                'lead_status' => $leadStatus,
                'lead_search' => $leadSearch,
                'leads_from_date' => (string) $request->query('leads_from_date', $request->query('from_date', '')),
                'leads_to_date' => (string) $request->query('leads_to_date', $request->query('to_date', '')),
            ],
            'widgetVisibility' => [
                'can_export_data' => !$this->isManagerRole((string) $request->user()?->role),
            ],
        ]);
    }

    public function adminPackageLeadsIndex(Request $request): View
    {
        $leadStatus = (string) $request->string('lead_status');
        $leadSearch = trim((string) $request->string('lead_search'));

        $leads = LeadProfile::query()
            ->with('conversation:id,channel,status,last_message_at')
            ->whereHas('conversation', function ($query): void {
                $query->where('channel', 'package_builder');
            })
            ->when($leadStatus !== '', function ($query) use ($leadStatus): void {
                $query->where('status', $leadStatus);
            })
            ->when($leadSearch !== '', function ($query) use ($leadSearch): void {
                $query->where(function ($inner) use ($leadSearch): void {
                    $inner->where('name', 'like', "%{$leadSearch}%")
                        ->orWhere('email', 'like', "%{$leadSearch}%")
                        ->orWhere('phone', 'like', "%{$leadSearch}%")
                        ->orWhere('service_type', 'like', "%{$leadSearch}%")
                        ->orWhere('location', 'like', "%{$leadSearch}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.package-leads-index', [
            'leads' => $leads,
            'filters' => [
                'lead_status' => $leadStatus,
                'lead_search' => $leadSearch,
                'leads_from_date' => (string) $request->query('leads_from_date', $request->query('from_date', '')),
                'leads_to_date' => (string) $request->query('leads_to_date', $request->query('to_date', '')),
            ],
            'widgetVisibility' => [
                'can_export_data' => !$this->isManagerRole((string) $request->user()?->role),
            ],
        ]);
    }

    public function adminQuotesIndex(Request $request): View
    {
        $quoteStatus = (string) $request->string('quote_status');
        $quoteSearch = trim((string) $request->string('quote_search'));
        $minTotal = $request->filled('min_total') ? (int) $request->input('min_total') : null;
        $maxTotal = $request->filled('max_total') ? (int) $request->input('max_total') : null;

        $quotes = QuoteBuild::query()
            ->when($quoteStatus !== '', function ($query) use ($quoteStatus): void {
                $query->where('status', $quoteStatus);
            })
            ->when($quoteSearch !== '', function ($query) use ($quoteSearch): void {
                $query->where(function ($inner) use ($quoteSearch): void {
                    $inner->where('quote_id', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_name', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_email', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_phone', 'like', "%{$quoteSearch}%");
                });
            })
            ->when($minTotal !== null, function ($query) use ($minTotal): void {
                $query->where('estimated_total', '>=', $minTotal);
            })
            ->when($maxTotal !== null, function ($query) use ($maxTotal): void {
                $query->where('estimated_total', '<=', $maxTotal);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.quotes-index', [
            'quotes' => $quotes,
            'filters' => [
                'quote_status' => $quoteStatus,
                'quote_search' => $quoteSearch,
                'min_total' => $minTotal,
                'max_total' => $maxTotal,
                'quotes_from_date' => (string) $request->query('quotes_from_date', $request->query('from_date', '')),
                'quotes_to_date' => (string) $request->query('quotes_to_date', $request->query('to_date', '')),
            ],
            'widgetVisibility' => [
                'can_export_data' => !$this->isManagerRole((string) $request->user()?->role),
            ],
        ]);
    }

    public function adminProjectsIndex(Request $request): View
    {
        $display = trim((string) $request->string('project_view'));
        if (!in_array($display, ['table', 'kanban'], true)) {
            $display = 'table';
        }

        $scope = trim((string) $request->string('project_scope'));
        if (!in_array($scope, ['ongoing', 'past', 'all'], true)) {
            $scope = 'ongoing';
        }

        $status = trim((string) $request->string('project_status'));
        $search = trim((string) $request->string('project_search'));
        $allowedStatuses = ['accepted', 'shooting', 'editing', 'complete'];

        $baseQuery = ClientProject::query()
            ->with(['client:id,name,email,phone'])
            ->when($scope !== 'all', function ($query) use ($scope): void {
                if ($scope === 'ongoing') {
                    $query->whereIn('status', ['accepted', 'shooting', 'editing']);
                    return;
                }

                $query->where('status', 'complete');
            })
            ->when($status !== '' && in_array($status, $allowedStatuses, true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('service_type', 'like', "%{$search}%")
                        ->orWhere('property_address', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            });

        $projects = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $kanbanProjects = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->limit(300)
            ->get();

        $totalProjects = ClientProject::count();
        $ongoingProjects = ClientProject::whereIn('status', ['accepted', 'shooting', 'editing'])->count();
        $completedProjects = ClientProject::where('status', 'complete')->count();
        $dueThisWeek = ClientProject::query()
            ->whereIn('status', ['accepted', 'shooting', 'editing'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
            ->count();
        $overdueProjects = ClientProject::query()
            ->whereIn('status', ['accepted', 'shooting', 'editing'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $canManageProjects = in_array(strtolower(trim((string) $request->user()?->role)), ['owner', 'admin', 'manager'], true);

        return view('admin.projects-index', [
            'projects' => $projects,
            'kpi' => [
                'total_projects' => $totalProjects,
                'ongoing_projects' => $ongoingProjects,
                'completed_projects' => $completedProjects,
                'due_this_week' => $dueThisWeek,
                'overdue_projects' => $overdueProjects,
            ],
            'filters' => [
                'project_view' => $display,
                'project_scope' => $scope,
                'project_status' => $status,
                'project_search' => $search,
            ],
            'kanbanProjects' => $kanbanProjects,
            'projectStatuses' => $allowedStatuses,
            'canManageProjects' => $canManageProjects,
        ]);
    }

    public function adminMediaDeliveryIndex(Request $request): View
    {
        $search = trim((string) $request->string('media_search'));

        $projects = ClientProject::query()
            ->with([
                'client:id,name,email,phone',
                'media' => function ($query): void {
                    $query->latest('id');
                },
                'invoices:id,client_project_id,status',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('service_type', 'like', "%{$search}%")
                        ->orWhere('property_address', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $canManageMedia = in_array(strtolower(trim((string) $request->user()?->role)), ['owner', 'admin', 'manager'], true);
        $canViewInvoices = in_array(strtolower(trim((string) $request->user()?->role)), ['owner', 'admin', 'manager'], true);
        $galleryPayloadByProject = $this->buildProjectGalleryPayloadMap($projects->getCollection(), false, true, false);

        return view('admin.media-delivery-index', [
            'projects' => $projects,
            'filters' => [
                'media_search' => $search,
            ],
            'canManageMedia' => $canManageMedia,
            'canViewInvoices' => $canViewInvoices,
            'galleryPayloadByProject' => $galleryPayloadByProject,
        ]);
    }

    public function adminMediaWatermarkSettingsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $settings = $this->getWatermarkSettings();
        $renderConfig = $this->resolveWatermarkRenderConfig($settings);
        $signature = (string) ($renderConfig['signature'] ?? '');
        $logoExists = !blank($settings->logo_path)
            && Storage::disk((string) ($settings->logo_disk ?: 'public'))->exists((string) $settings->logo_path);

        $unpaidImageTotal = ClientProjectMedia::query()
            ->where('type', 'image')
            ->whereHas('project', function ($query): void {
                $query->whereDoesntHave('invoices', function ($invoiceQuery): void {
                    $invoiceQuery->where('status', 'paid');
                });
            })
            ->count();

        $upToDateWatermarks = ClientProjectMedia::query()
            ->where('type', 'image')
            ->where('watermark_signature', $signature)
            ->whereHas('project', function ($query): void {
                $query->whereDoesntHave('invoices', function ($invoiceQuery): void {
                    $invoiceQuery->where('status', 'paid');
                });
            })
            ->count();

        $pendingRebuild = max(0, $unpaidImageTotal - $upToDateWatermarks);

        return view('admin.media-watermark-settings', [
            'settings' => $settings,
            'logoExists' => $logoExists,
            'unpaidImageTotal' => $unpaidImageTotal,
            'upToDateWatermarks' => $upToDateWatermarks,
            'pendingRebuild' => $pendingRebuild,
        ]);
    }

    public function adminMediaWatermarkSettingsUpdate(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'watermark_logo' => ['nullable', 'file', 'mimes:png', 'max:10240'],
            'position' => ['required', 'in:top_left,top_right,bottom_left,bottom_right,center'],
            'size' => ['required', 'in:small,medium,large'],
            'opacity_percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $settings = $this->getWatermarkSettings();
        $oldDisk = (string) ($settings->logo_disk ?: 'public');
        $oldPath = (string) ($settings->logo_path ?? '');

        if ($request->hasFile('watermark_logo')) {
            $storedPath = $request->file('watermark_logo')->store('watermark', 'public');
            if ($storedPath) {
                $settings->logo_disk = 'public';
                $settings->logo_path = $storedPath;

                if ($oldPath !== '' && Storage::disk($oldDisk)->exists($oldPath) && $oldPath !== $storedPath) {
                    Storage::disk($oldDisk)->delete($oldPath);
                }
            }
        }

        $settings->position = (string) $validated['position'];
        $settings->size = (string) $validated['size'];
        $settings->opacity_percent = (int) $validated['opacity_percent'];
        $settings->save();

        $hasLogo = !blank($settings->logo_path)
            && Storage::disk((string) ($settings->logo_disk ?: 'public'))->exists((string) $settings->logo_path);
        $status = $hasLogo
            ? 'Watermark settings saved. Existing unpaid previews will refresh automatically with the new logo settings.'
            : 'Settings saved, but no PNG logo is uploaded yet. Upload a logo to apply branded watermark previews.';

        return back()->with('status', $status);
    }

    public function adminMediaWatermarkRebuild(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $renderConfig = $this->resolveWatermarkRenderConfig();
        $logoPath = (string) ($renderConfig['logo_absolute_path'] ?? '');
        if ($logoPath === '' || !is_file($logoPath)) {
            return back()->withErrors(['watermark_logo' => 'Please upload a PNG watermark logo before running rebuild.']);
        }

        $signature = (string) ($renderConfig['signature'] ?? '');
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        ClientProjectMedia::query()
            ->where('type', 'image')
            ->with(['project.invoices:id,client_project_id,status'])
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$processed, &$skipped, &$failed, $renderConfig, $signature): void {
                foreach ($items as $item) {
                    try {
                        if (!$item instanceof ClientProjectMedia) {
                            continue;
                        }

                        $project = $item->project;
                        if (!$project instanceof ClientProject) {
                            $failed++;
                            continue;
                        }

                        $isPaid = $project->invoices->contains(static fn (ClientInvoice $invoice): bool => $invoice->status === 'paid');
                        if ($isPaid) {
                            $skipped++;
                            continue;
                        }

                        $generated = $this->generateHardWatermarkVariant((string) $item->disk, (string) $item->path, $this->projectMediaBasePath($project), $renderConfig);
                        if (!is_array($generated) || blank($generated['path'] ?? null)) {
                            $failed++;
                            continue;
                        }

                        $newDisk = (string) ($generated['disk'] ?? (string) $item->disk);
                        $newPath = (string) ($generated['path'] ?? '');
                        if ($newPath === '') {
                            $failed++;
                            continue;
                        }

                        $oldDisk = (string) ($item->watermark_disk ?: '');
                        $oldPath = (string) ($item->watermark_path ?: '');
                        if ($oldDisk !== '' && $oldPath !== '' && ($oldDisk !== $newDisk || $oldPath !== $newPath) && Storage::disk($oldDisk)->exists($oldPath)) {
                            Storage::disk($oldDisk)->delete($oldPath);
                        }

                        $item->watermark_disk = $newDisk;
                        $item->watermark_path = $newPath;
                        $item->watermark_signature = $signature;
                        $item->save();

                        $processed++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });

        return back()->with('status', "Watermark rebuild completed. Updated: {$processed}, skipped paid: {$skipped}, failed: {$failed}.");
    }

    public function adminMediaFolderMigrationRun(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        try {
            Artisan::call('media:migrate-project-folders');
            $output = (string) Artisan::output();

            $moved = 0;
            $updated = 0;
            $missing = 0;
            $errors = 0;

            if (preg_match('/Moved files:\s*(\d+)/i', $output, $matchMoved)) {
                $moved = (int) ($matchMoved[1] ?? 0);
            }

            if (preg_match('/Updated DB rows:\s*(\d+)/i', $output, $matchUpdated)) {
                $updated = (int) ($matchUpdated[1] ?? 0);
            }

            if (preg_match('/Missing files:\s*(\d+)/i', $output, $matchMissing)) {
                $missing = (int) ($matchMissing[1] ?? 0);
            }

            if (preg_match('/Errors:\s*(\d+)/i', $output, $matchErrors)) {
                $errors = (int) ($matchErrors[1] ?? 0);
            }

            if ($errors > 0) {
                return back()->withErrors([
                    'media_migration' => "Media folder migration finished with errors. Moved: {$moved}, Updated DB: {$updated}, Missing: {$missing}, Errors: {$errors}.",
                ]);
            }

            return back()->with('status', "Media folder migration completed. Moved: {$moved}, Updated DB: {$updated}, Missing: {$missing}, Errors: {$errors}.");
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['media_migration' => 'Could not run media folder migration. Please try again.']);
        }
    }

    public function adminMediaWatermarkLogoView(Request $request)
    {
        $this->ensurePipelineWriteAccess($request);

        $settings = $this->getWatermarkSettings();
        $disk = (string) ($settings->logo_disk ?: 'public');
        $path = (string) ($settings->logo_path ?? '');

        if ($path === '' || !Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="watermark-logo.png"',
        ]);
    }

    public function adminInvoicesIndex(Request $request): View
    {
        $statusFilter = trim((string) $request->string('invoice_status'));
        $search = trim((string) $request->string('invoice_search'));
        $projectFilter = $request->filled('invoice_project') ? (int) $request->input('invoice_project') : null;

        $projectFilterTitle = null;
        if ($projectFilter !== null && $projectFilter > 0) {
            $projectFilterTitle = ClientProject::query()
                ->where('id', $projectFilter)
                ->value('title');
        }

        $baseQuery = ClientInvoice::query()
            ->with([
                'client:id,name,email,phone',
                'project:id,title',
            ])
            ->when($projectFilter !== null && $projectFilter > 0, function ($query) use ($projectFilter): void {
                $query->where('client_project_id', $projectFilter);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function ($projectQuery) use ($search): void {
                            $projectQuery->where('title', 'like', "%{$search}%");
                        });
                });
            });

        $invoices = (clone $baseQuery)
            ->when($statusFilter !== '', function ($query) use ($statusFilter): void {
                if ($statusFilter === 'unpaid') {
                    $query->where('status', '!=', 'paid');
                    return;
                }

                if (in_array($statusFilter, ['paid', 'draft', 'sent', 'partial', 'overdue'], true)) {
                    $query->where('status', $statusFilter);
                }
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totalInvoices = (clone $baseQuery)->count();
        $paidInvoices = (clone $baseQuery)->where('status', 'paid')->count();
        $unpaidInvoices = (clone $baseQuery)->where('status', '!=', 'paid')->count();
        $overdueInvoices = (clone $baseQuery)
            ->where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        $totalAmount = (float) ((clone $baseQuery)->sum('amount') ?? 0);
        $paidAmount = (float) ((clone $baseQuery)->where('status', 'paid')->sum('amount') ?? 0);

        return view('admin.invoices-index', [
            'invoices' => $invoices,
            'kpi' => [
                'total_invoices' => $totalInvoices,
                'paid_invoices' => $paidInvoices,
                'unpaid_invoices' => $unpaidInvoices,
                'overdue_invoices' => $overdueInvoices,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => max(0, $totalAmount - $paidAmount),
            ],
            'filters' => [
                'invoice_status' => $statusFilter,
                'invoice_search' => $search,
                'invoice_project' => $projectFilter,
                'invoice_project_title' => $projectFilterTitle,
            ],
        ]);
    }

    public function adminEmailsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $defaultRecipient = (string) env('QUOTE_ADMIN_EMAIL', (string) config('mail.lead_alert_address', (string) config('mail.from.address')));

        $quickTemplates = [
            [
                'key' => 'delivery_test',
                'title' => 'Delivery Test',
                'description' => 'Confirm SendGrid delivery and inbox routing in one click.',
            ],
            [
                'key' => 'pipeline_snapshot',
                'title' => 'Pipeline Snapshot',
                'description' => 'Send a compact summary of leads, quotes, and invoices.',
            ],
            [
                'key' => 'followup_digest',
                'title' => 'Follow-up Digest',
                'description' => 'Send an operational reminder focused on pending pipeline actions.',
            ],
        ];

        $quickTemplates = array_map(function (array $template): array {
            $resolved = $this->buildAdminEmailTemplate((string) ($template['key'] ?? ''));
            $template['subject_preview'] = (string) ($resolved['subject'] ?? '');
            return $template;
        }, $quickTemplates);

        $pipelineSummary = [
            'leads_new' => LeadProfile::query()->where('status', 'new')->count(),
            'leads_qualified' => LeadProfile::query()->where('status', 'qualified')->count(),
            'quotes_new' => QuoteBuild::query()->where('status', 'new')->count(),
            'quotes_booked' => QuoteBuild::query()->where('status', 'booked')->count(),
            'invoices_overdue' => ClientInvoice::query()
                ->where('status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        $projectOptions = ClientProject::query()
            ->with('client:id,name,email')
            ->latest('id')
            ->limit(300)
            ->get(['id', 'client_id', 'title', 'service_type', 'status'])
            ->map(static function (ClientProject $project): array {
                $clientName = trim((string) ($project->client?->name ?? 'Unknown client'));
                $clientEmail = trim((string) ($project->client?->email ?? ''));
                $service = trim((string) ($project->service_type ?? ''));
                $status = trim((string) ($project->status ?? ''));

                $labelParts = ["#{$project->id}", $clientName, $project->title];
                if ($service !== '') {
                    $labelParts[] = $service;
                }
                if ($status !== '') {
                    $labelParts[] = strtoupper($status);
                }
                if ($clientEmail !== '') {
                    $labelParts[] = $clientEmail;
                }

                return [
                    'id' => (int) $project->id,
                    'label' => implode(' | ', array_filter($labelParts, static fn ($value): bool => trim((string) $value) !== '')),
                    'client_email' => $clientEmail !== '' ? Str::lower($clientEmail) : null,
                ];
            })
            ->values()
            ->all();

        try {
            $emailLogs = EmailLog::query()
                ->with('creator:id,name,email')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();

            $timelineMap = collect();
            $emailLogIds = $emailLogs->getCollection()->pluck('id')->values()->all();
            if (count($emailLogIds) > 0) {
                $timelineMap = SendgridWebhookEvent::query()
                    ->whereIn('email_log_id', $emailLogIds)
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('email_log_id')
                    ->map(static fn ($items) => $items->take(8)->values());
            }
        } catch (Throwable $exception) {
            report($exception);
            $emailLogs = new LengthAwarePaginator([], 0, 15, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            $timelineMap = collect();
        }

        return view('admin.emails-index', [
            'defaultRecipient' => $defaultRecipient,
            'quickTemplates' => $quickTemplates,
            'pipelineSummary' => $pipelineSummary,
            'projectOptions' => $projectOptions,
            'emailLogs' => $emailLogs,
            'emailEventTimeline' => $timelineMap,
        ]);
    }

    public function adminEmailSend(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'mode' => ['required', 'in:template,custom'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'client_project_id' => ['nullable', 'integer'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'cc' => ['nullable', 'string', 'max:500'],
            'bcc' => ['nullable', 'string', 'max:500'],
            'template_key' => ['nullable', 'string', 'in:delivery_test,pipeline_snapshot,followup_digest'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['nullable', 'string', 'max:10000'],
        ]);

        $recipient = trim((string) ($validated['recipient_email'] ?? ''));
        $ccList = $this->parseEmailList((string) ($validated['cc'] ?? ''));
        $bccList = $this->parseEmailList((string) ($validated['bcc'] ?? ''));

        if (count($ccList['invalid']) > 0) {
            return back()->withErrors(['cc' => 'Invalid CC email(s): ' . implode(', ', $ccList['invalid'])])->withInput();
        }

        if (count($bccList['invalid']) > 0) {
            return back()->withErrors(['bcc' => 'Invalid BCC email(s): ' . implode(', ', $bccList['invalid'])])->withInput();
        }

        $subject = '';
        $body = '';

        if (($validated['mode'] ?? 'custom') === 'template') {
            $template = $this->buildAdminEmailTemplate((string) ($validated['template_key'] ?? ''));
            if ($template === null) {
                return back()->withErrors(['template_key' => 'Please choose a valid quick-send template.'])->withInput();
            }

            $subject = $template['subject'];
            $body = $template['body'];
        } else {
            $subject = trim((string) ($validated['subject'] ?? ''));
            $body = trim((string) ($validated['message'] ?? ''));
            if ($subject === '') {
                return back()->withErrors(['subject' => 'Subject is required for custom emails.'])->withInput();
            }
            if ($body === '') {
                return back()->withErrors(['message' => 'Message is required for custom emails.'])->withInput();
            }
        }

        $threadProjectId = $this->resolveOutboundThreadProjectId(
            recipientEmail: $recipient,
            requestedProjectId: !blank($validated['client_project_id'] ?? null) ? (int) $validated['client_project_id'] : null,
        );
        if ($threadProjectId !== null) {
            $subject = $this->appendProjectThreadTag($subject, $threadProjectId);
        }

        $emailLog = $this->createEmailLogEntry([
            'created_by' => $request->user()?->id,
            'mode' => (string) ($validated['mode'] ?? 'custom'),
            'template_key' => (string) ($validated['template_key'] ?? ''),
            'recipient_email' => $recipient,
            'reply_to' => filled($validated['reply_to'] ?? null) ? (string) $validated['reply_to'] : null,
            'cc' => count($ccList['valid']) > 0 ? implode(', ', $ccList['valid']) : null,
            'bcc' => count($bccList['valid']) > 0 ? implode(', ', $bccList['valid']) : null,
            'subject' => $subject,
            'body_preview' => Str::limit($body, 700),
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
            'provider_status' => 'queued',
        ]);

        try {
            $mailer = Mail::to($recipient);
            if (count($ccList['valid']) > 0) {
                $mailer->cc($ccList['valid']);
            }
            if (count($bccList['valid']) > 0) {
                $mailer->bcc($bccList['valid']);
            }

            $mailer->send(new BrandedNotificationMail(
                subjectLine: $subject,
                heading: 'Message from Maccento CRM',
                bodyLines: $this->emailBodyToLines($body),
                intro: 'This message was sent from your CRM Email Center.',
                ctaLabel: 'Open Maccento CRM',
                ctaUrl: route('admin.emails.index'),
                footerNote: 'Need help? Reply to this email and our team will assist you.',
                emailLogId: $emailLog?->id,
                threadProjectId: $threadProjectId,
                replyToAddress: filled($validated['reply_to'] ?? null) ? (string) $validated['reply_to'] : null,
            ));

            $this->notificationService()->notifyInternal(
                'admin_email_sent',
                'CRM email sent',
                "Email sent to {$recipient}: {$subject}",
                route('admin.emails.index'),
                ['recipient' => $recipient, 'subject' => $subject]
            );

            if ($emailLog !== null) {
                $emailLog->forceFill([
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => now(),
                    'provider_status' => 'processed',
                    'provider_last_event_at' => now(),
                ])->save();
            } else {
                $this->createEmailLogEntry([
                    'created_by' => $request->user()?->id,
                    'mode' => (string) ($validated['mode'] ?? 'custom'),
                    'template_key' => (string) ($validated['template_key'] ?? ''),
                    'recipient_email' => $recipient,
                    'reply_to' => filled($validated['reply_to'] ?? null) ? (string) $validated['reply_to'] : null,
                    'cc' => count($ccList['valid']) > 0 ? implode(', ', $ccList['valid']) : null,
                    'bcc' => count($bccList['valid']) > 0 ? implode(', ', $bccList['valid']) : null,
                    'subject' => $subject,
                    'body_preview' => Str::limit($body, 700),
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => now(),
                    'provider_status' => 'processed',
                    'provider_last_event_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            if ($emailLog !== null) {
                $emailLog->forceFill([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 500),
                    'provider_status' => 'failed',
                    'provider_last_event_at' => now(),
                ])->save();
            } else {
                $this->createEmailLogEntry([
                    'created_by' => $request->user()?->id,
                    'mode' => (string) ($validated['mode'] ?? 'custom'),
                    'template_key' => (string) ($validated['template_key'] ?? ''),
                    'recipient_email' => $recipient,
                    'reply_to' => filled($validated['reply_to'] ?? null) ? (string) $validated['reply_to'] : null,
                    'cc' => count($ccList['valid']) > 0 ? implode(', ', $ccList['valid']) : null,
                    'bcc' => count($bccList['valid']) > 0 ? implode(', ', $bccList['valid']) : null,
                    'subject' => $subject,
                    'body_preview' => Str::limit($body, 700),
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 500),
                    'sent_at' => null,
                    'provider_status' => 'failed',
                    'provider_last_event_at' => now(),
                ]);
            }

            report($exception);
            return back()->withErrors(['recipient_email' => 'Email could not be sent. Please verify SendGrid and try again.'])->withInput();
        }

        return redirect()->route('admin.emails.index')->with('status', 'Email sent successfully.');
    }

    public function adminQuoteManualStore(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'services' => ['required', 'string', 'max:255'],
            'listing_type' => ['nullable', 'in:home,condo,rental,chalet,other'],
            'estimated_total' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($validated['contact_email'] ?? null) && blank($validated['contact_phone'] ?? null)) {
            return back()->withErrors(['contact_email' => 'Please provide at least email or phone for the quote.'])->withInput();
        }

        $services = collect(explode(',', (string) $validated['services']))
            ->map(static fn (string $item): string => trim($item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        if (count($services) === 0) {
            return back()->withErrors(['services' => 'Please provide at least one service.'])->withInput();
        }

        $linkedLeadId = null;
        if (!blank($validated['contact_email'] ?? null) || !blank($validated['contact_phone'] ?? null)) {
            $linkedLeadId = LeadProfile::query()
                ->when(!blank($validated['contact_email'] ?? null), function ($query) use ($validated): void {
                    $query->where('email', (string) $validated['contact_email']);
                })
                ->when(!blank($validated['contact_phone'] ?? null), function ($query) use ($validated): void {
                    $query->orWhere('phone', (string) $validated['contact_phone']);
                })
                ->value('id');
        }

        $quote = QuoteBuild::create([
            'quote_id' => QuoteBuild::makeQuoteId(),
            'user_id' => null,
            'conversation_id' => null,
            'lead_profile_id' => $linkedLeadId,
            'visitor_id' => null,
            'status' => 'new',
            'listing_type' => $validated['listing_type'] ?? 'other',
            'services' => $services,
            'options' => [
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
            ],
            'line_items' => collect($services)->map(static function (string $service): array {
                return ['label' => ucfirst($service), 'amount' => 0];
            })->all(),
            'estimated_total' => (int) $validated['estimated_total'],
            'currency' => strtoupper(trim((string) ($validated['currency'] ?? 'USD'))),
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        QuoteEvent::create([
            'quote_build_id' => $quote->id,
            'event_type' => 'manual_quote_created',
            'payload' => [
                'estimated_total' => $quote->estimated_total,
                'services' => $services,
            ],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.quotes.show', $quote)->with('status', 'Quote created successfully.');
    }

    public function adminExportLeadsCsv(Request $request): StreamedResponse
    {
        abort_unless($this->canExportData($request), 403);

        $leadStatus = (string) $request->string('lead_status');
        $leadSearch = trim((string) $request->string('lead_search'));
        $leadChannel = trim((string) $request->string('lead_channel'));
        [$fromDate, $toDate] = $this->extractDateRange($request, 'from_date', 'to_date');

        $rows = LeadProfile::query()
            ->when($leadChannel !== '', function ($query) use ($leadChannel): void {
                $query->whereHas('conversation', function ($conversationQuery) use ($leadChannel): void {
                    $conversationQuery->where('channel', $leadChannel);
                });
            })
            ->when($leadStatus !== '', function ($query) use ($leadStatus): void {
                $query->where('status', $leadStatus);
            })
            ->when($leadSearch !== '', function ($query) use ($leadSearch): void {
                $query->where(function ($inner) use ($leadSearch): void {
                    $inner->where('name', 'like', "%{$leadSearch}%")
                        ->orWhere('email', 'like', "%{$leadSearch}%")
                        ->orWhere('phone', 'like', "%{$leadSearch}%")
                        ->orWhere('service_type', 'like', "%{$leadSearch}%")
                        ->orWhere('location', 'like', "%{$leadSearch}%");
                });
            })
            ->when($fromDate !== null, function ($query) use ($fromDate): void {
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate !== null, function ($query) use ($toDate): void {
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'phone', 'service_type', 'property_type', 'location', 'status', 'score', 'created_at']);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Service', 'Property Type', 'Location', 'Status', 'Score', 'Created']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $this->csvSafe($row->name),
                    $this->csvSafe($row->email),
                    $this->csvSafe($row->phone),
                    $this->csvSafe($row->service_type),
                    $this->csvSafe($row->property_type),
                    $this->csvSafe($row->location),
                    $row->status,
                    $row->score,
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, 'leads-export-' . now()->format('Ymd-His') . '.csv');
    }

    public function adminExportQuotesCsv(Request $request): StreamedResponse
    {
        abort_unless($this->canExportData($request), 403);

        $quoteStatus = (string) $request->string('quote_status');
        $quoteSearch = trim((string) $request->string('quote_search'));
        $minTotal = $request->filled('min_total') ? (int) $request->input('min_total') : null;
        $maxTotal = $request->filled('max_total') ? (int) $request->input('max_total') : null;
        [$fromDate, $toDate] = $this->extractDateRange($request, 'from_date', 'to_date');

        $rows = QuoteBuild::query()
            ->when($quoteStatus !== '', function ($query) use ($quoteStatus): void {
                $query->where('status', $quoteStatus);
            })
            ->when($quoteSearch !== '', function ($query) use ($quoteSearch): void {
                $query->where(function ($inner) use ($quoteSearch): void {
                    $inner->where('quote_id', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_name', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_email', 'like', "%{$quoteSearch}%")
                        ->orWhere('options->contact_phone', 'like', "%{$quoteSearch}%");
                });
            })
            ->when($minTotal !== null, function ($query) use ($minTotal): void {
                $query->where('estimated_total', '>=', $minTotal);
            })
            ->when($maxTotal !== null, function ($query) use ($maxTotal): void {
                $query->where('estimated_total', '<=', $maxTotal);
            })
            ->when($fromDate !== null, function ($query) use ($fromDate): void {
                $query->whereDate('submitted_at', '>=', $fromDate);
            })
            ->when($toDate !== null, function ($query) use ($toDate): void {
                $query->whereDate('submitted_at', '<=', $toDate);
            })
            ->orderByDesc('id')
            ->get(['quote_id', 'status', 'estimated_total', 'currency', 'services', 'options', 'submitted_at', 'created_at']);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Quote ID', 'Status', 'Total', 'Currency', 'Services', 'Contact Name', 'Contact Email', 'Contact Phone', 'Submitted', 'Created']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->quote_id,
                    $row->status,
                    $row->estimated_total,
                    $row->currency,
                    $this->csvSafe(is_array($row->services) ? implode('|', $row->services) : ''),
                    $this->csvSafe((string) data_get($row->options, 'contact_name', '')),
                    $this->csvSafe((string) data_get($row->options, 'contact_email', '')),
                    $this->csvSafe((string) data_get($row->options, 'contact_phone', '')),
                    optional($row->submitted_at)->format('Y-m-d H:i:s'),
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, 'quotes-export-' . now()->format('Ymd-His') . '.csv');
    }

    public function adminExportFollowUpsCsv(Request $request): StreamedResponse
    {
        abort_unless($this->canExportData($request), 403);

        $status = (string) $request->string('status');
        [$fromDate, $toDate] = $this->extractDateRange($request, 'from_date', 'to_date');

        $rows = FollowUp::query()
            ->with(['leadProfile:id,name,email,phone', 'owner:id,name,email'])
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($fromDate !== null, function ($query) use ($fromDate): void {
                $query->whereDate('due_at', '>=', $fromDate);
            })
            ->when($toDate !== null, function ($query) use ($toDate): void {
                $query->whereDate('due_at', '<=', $toDate);
            })
            ->orderByRaw('CASE WHEN status = ? AND due_at < ? THEN 0 ELSE 1 END', ['pending', now()])
            ->orderBy('due_at')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Follow-up ID', 'Due At', 'Overdue', 'Method', 'Status', 'Lead Name', 'Lead Email', 'Lead Phone', 'Owner', 'Owner Email', 'Notes']);
            foreach ($rows as $row) {
                $overdue = $row->status === 'pending' && $row->due_at && $row->due_at->isPast();
                fputcsv($out, [
                    $row->id,
                    optional($row->due_at)->format('Y-m-d H:i:s'),
                    $overdue ? 'yes' : 'no',
                    $row->method,
                    $row->status,
                    $this->csvSafe($row->leadProfile?->name),
                    $this->csvSafe($row->leadProfile?->email),
                    $this->csvSafe($row->leadProfile?->phone),
                    $this->csvSafe($row->owner?->name),
                    $this->csvSafe($row->owner?->email),
                    $this->csvSafe($row->result_notes),
                ]);
            }
            fclose($out);
        }, 'followups-export-' . now()->format('Ymd-His') . '.csv');
    }

    public function adminLeadShow(LeadProfile $lead): View
    {
        $lead->load([
            'events.creator:id,name,email',
            'followUps.owner:id,name,email',
            'conversation.messages:id,conversation_id,role,content,model,metadata,created_at',
        ]);

        return view('admin.lead-show', [
            'lead' => $lead,
        ]);
    }

    public function adminLeadConversationPdf(LeadProfile $lead)
    {
        $lead->load([
            'conversation.messages:id,conversation_id,role,content,created_at',
        ]);

        $messages = ($lead->conversation?->messages ?? collect())->values();
        $safeName = trim((string) ($lead->name ?: 'lead-' . $lead->id));
        $safeName = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $safeName) ?: 'lead-' . $lead->id;
        $filename = 'conversation-' . $safeName . '-' . now()->format('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('admin.pdf.lead-conversation', [
            'lead' => $lead,
            'messages' => $messages,
        ])->setPaper('a4');

        return $pdf->download($filename);
    }

    public function adminFormSubmissions(Request $request): View
    {
        $status = (string) $request->string('status');
        $search = trim((string) $request->string('search'));

        $submissions = WebsiteFormSubmission::query()
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%")
                        ->orWhere('region', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.form-submissions', [
            'submissions' => $submissions,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function adminFormSubmissionShow(WebsiteFormSubmission $submission): View
    {
        return view('admin.form-submission-show', [
            'submission' => $submission,
        ]);
    }

    public function adminFormSubmissionStatusUpdate(Request $request, WebsiteFormSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,reviewed,qualified,won,lost'],
        ]);

        $submission->status = $validated['status'];
        $submission->save();

        return back()->with('status', 'Submission status updated.');
    }

    public function adminClientsIndex(Request $request): View
    {
        $status = trim((string) $request->string('status'));
        $search = trim((string) $request->string('search'));

        $clients = Client::query()
            ->withCount(['projects', 'invoices', 'serviceRequests'])
            ->with([
                'projects' => function ($query): void {
                    $query->latest('id')->limit(1);
                },
            ])
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $recentRequests = ClientServiceRequest::query()
            ->with('client:id,name,email,phone')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.clients-index', [
            'clients' => $clients,
            'recentRequests' => $recentRequests,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function adminClientStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:client,agent'],
            'status' => ['required', 'in:active,vip,inactive'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $passwordForAdmin = (string) $validated['password'];
        $accountMessage = '';

        $linkedUser = User::query()
            ->where('email', $email)
            ->first();

        if ($linkedUser) {
            if (blank($linkedUser->phone) && !blank($validated['phone'] ?? null)) {
                $linkedUser->phone = (string) $validated['phone'];
            }
            $linkedUser->password = $passwordForAdmin;
            $linkedUser->save();
            $accountMessage = 'Existing login account linked.';
        } else {
            $linkedUser = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'password' => $passwordForAdmin,
            ]);
            $accountMessage = 'Login created.';
        }

        if (!in_array((string) $linkedUser->role, ['client', 'agent'], true)) {
            $linkedUser->role = $validated['role'];
            $linkedUser->save();
        }

        $client = Client::create([
            'user_id' => $linkedUser->id,
            'created_by' => $request->user()?->id,
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.clients.show', $client)->with('status', "Client created. {$accountMessage} Login password has been set.");
    }

    public function adminUsersIndex(Request $request): View
    {
        $roleFilter = (string) $request->string('role');
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->when($roleFilter !== '', function ($query) use ($roleFilter): void {
                $query->where('role', $roleFilter);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users-index', [
            'users' => $users,
            'filters' => [
                'role' => $roleFilter,
                'search' => $search,
            ],
            'roles' => ['admin', 'manager', 'photographer', 'editor', 'client', 'agent'],
        ]);
    }

    public function adminUserStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,manager,photographer,editor,client,agent'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $passwordForAdmin = trim((string) ($validated['password'] ?? ''));
        if ($passwordForAdmin === '') {
            $passwordForAdmin = 'Maccento@' . strtoupper(Str::random(6));
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => $passwordForAdmin,
        ]);

        if (in_array($validated['role'], ['client', 'agent'], true)) {
            Client::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'created_by' => $request->user()?->id,
                    'name' => $validated['name'],
                    'email' => $email,
                    'phone' => $validated['phone'] ?? null,
                    'company' => $validated['company'] ?? null,
                    'status' => 'active',
                    'notes' => $validated['notes'] ?? 'Created from user account manager.',
                ]
            );
        }

        return back()->with('status', "Account created ({$validated['role']}). Temporary password: {$passwordForAdmin}");
    }

    public function adminUserDestroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        if ((int) $request->user()?->id === (int) $user->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account while logged in.']);
        }

        $display = $user->email ?: $user->name;
        $user->delete();

        return back()->with('status', "User {$display} deleted successfully.");
    }

    public function adminClientDestroy(Request $request, Client $client): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $clientName = $client->name ?: ('Client #' . $client->id);
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', "{$clientName} deleted successfully.");
    }

    public function adminClientShow(Client $client): View
    {
        $client->load([
            'projects' => function ($query): void {
                $query->latest('id')->with([
                    'creator:id,name,email',
                    'media' => function ($mediaQuery): void {
                        $mediaQuery->latest('id');
                    },
                    'invoices:id,client_project_id,status',
                ]);
            },
            'invoices' => function ($query): void {
                $query->latest('id')->with('project:id,title');
            },
            'messages' => function ($query): void {
                $query->latest('id')->with('sender:id,name,email');
            },
            'serviceRequests' => function ($query): void {
                $query->latest('id')->with('requester:id,name,email');
            },
        ]);

        return view('admin.client-show', [
            'client' => $client,
            'projectStatuses' => ['accepted', 'shooting', 'editing', 'complete'],
        ]);
    }

    public function adminClientProjectStore(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:120'],
            'property_address' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', 'in:accepted,shooting,editing,complete'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        ClientProject::create([
            'client_id' => $client->id,
            'created_by' => $request->user()?->id,
            'title' => $validated['title'],
            'service_type' => $validated['service_type'] ?? null,
            'property_address' => $validated['property_address'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Project created.');
    }

    public function adminClientProjectStatusUpdate(Request $request, ClientProject $project): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:accepted,shooting,editing,complete'],
        ]);

        $project->status = $validated['status'];
        $project->save();

        if ($project->client_id) {
            $client = Client::query()->find($project->client_id);
            if ($client) {
                $this->notifyClientUser(
                    $client,
                    'project_status_updated',
                    'Project status updated',
                    "Project \"{$project->title}\" is now {$project->status}.",
                    route('user.dashboard')
                );
            }
        }

        return back()->with('status', 'Project status updated.');
    }

    public function adminProjectMediaStore(Request $request, ClientProject $project): RedirectResponse
    {
        $validated = $request->validate([
            'media_files' => ['required', 'array', 'min:1'],
            'media_files.*' => ['required', 'file', 'max:512000'],
        ]);

        $saved = 0;
        $projectMediaBasePath = $this->projectMediaBasePath($project);
        $watermarkSettings = $this->getWatermarkSettings();
        $watermarkRenderConfig = $this->resolveWatermarkRenderConfig($watermarkSettings);
        $watermarkSignature = (string) ($watermarkRenderConfig['signature'] ?? '');

        foreach (($validated['media_files'] ?? []) as $file) {
            $mimeType = (string) ($file->getClientMimeType() ?: '');
            $type = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'other');
            if (!in_array($type, ['image', 'video'], true)) {
                continue;
            }

            $storedPath = $file->store("{$projectMediaBasePath}/gallery", 'public');
            if (!$storedPath) {
                continue;
            }

            $watermarkDisk = null;
            $watermarkPath = null;
            $mediaWatermarkSignature = null;
            if ($type === 'image') {
                $watermarked = $this->generateHardWatermarkVariant('public', $storedPath, $projectMediaBasePath, $watermarkRenderConfig);
                if (is_array($watermarked)) {
                    $watermarkDisk = (string) ($watermarked['disk'] ?? 'public');
                    $watermarkPath = (string) ($watermarked['path'] ?? '');
                    if ($watermarkPath === '') {
                        $watermarkDisk = null;
                        $watermarkPath = null;
                    } else {
                        $mediaWatermarkSignature = $watermarkSignature;
                    }
                }
            }

            ClientProjectMedia::create([
                'client_project_id' => $project->id,
                'uploaded_by' => $request->user()?->id,
                'type' => $type,
                'disk' => 'public',
                'path' => $storedPath,
                'watermark_disk' => $watermarkDisk,
                'watermark_path' => $watermarkPath,
                'watermark_signature' => $mediaWatermarkSignature,
                'original_name' => (string) ($file->getClientOriginalName() ?: basename($storedPath)),
                'mime_type' => $mimeType !== '' ? $mimeType : null,
                'size_bytes' => (int) $file->getSize(),
            ]);

            $saved++;
        }

        if ($saved === 0) {
            return back()->withErrors(['media_files' => 'No valid image or video files were uploaded.']);
        }

        return back()->with('status', "{$saved} gallery file(s) uploaded.");
    }

    public function adminProjectDeliveryZipStore(Request $request, ClientProject $project): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_zip' => ['required', 'file', 'mimes:zip', 'max:1024000'],
        ]);

        $file = $validated['delivery_zip'];
        $storedPath = $file->store($this->projectMediaBasePath($project) . '/delivery', 'public');

        ClientProjectMedia::create([
            'client_project_id' => $project->id,
            'uploaded_by' => $request->user()?->id,
            'type' => 'final_zip',
            'disk' => 'public',
            'path' => $storedPath,
            'original_name' => (string) ($file->getClientOriginalName() ?: basename($storedPath)),
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/zip'),
            'size_bytes' => (int) $file->getSize(),
        ]);

        return back()->with('status', 'Final delivery ZIP uploaded.');
    }

    public function adminProjectMediaView(Request $request, ClientProject $project, ClientProjectMedia $media)
    {
        if ((int) $media->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $disk = (string) $media->disk;
        $path = (string) $media->path;

        if ((string) $media->type === 'image' && !$this->projectIsPaid($project)) {
            $renderConfig = $this->resolveWatermarkRenderConfig();
            $signature = (string) ($renderConfig['signature'] ?? '');

            $existingWatermarkDisk = (string) ($media->watermark_disk ?: '');
            $existingWatermarkPath = (string) ($media->watermark_path ?: '');
            $hasExistingWatermark = $existingWatermarkDisk !== ''
                && $existingWatermarkPath !== ''
                && Storage::disk($existingWatermarkDisk)->exists($existingWatermarkPath);

            $needsRefresh = !$hasExistingWatermark || (string) ($media->watermark_signature ?? '') !== $signature;

            if ($needsRefresh) {
                $generated = $this->generateHardWatermarkVariant((string) $media->disk, (string) $media->path, $this->projectMediaBasePath($project), $renderConfig);
                if (is_array($generated) && !blank($generated['path'])) {
                    if ($hasExistingWatermark && ($existingWatermarkDisk !== (string) ($generated['disk'] ?? '') || $existingWatermarkPath !== (string) ($generated['path'] ?? ''))) {
                        Storage::disk($existingWatermarkDisk)->delete($existingWatermarkPath);
                    }

                    $media->watermark_disk = (string) ($generated['disk'] ?? (string) $media->disk);
                    $media->watermark_path = (string) ($generated['path'] ?? '');
                    $media->watermark_signature = $signature;
                    $media->save();

                    $disk = (string) $media->watermark_disk;
                    $path = (string) $media->watermark_path;
                } elseif ($hasExistingWatermark) {
                    $disk = $existingWatermarkDisk;
                    $path = $existingWatermarkPath;
                }
            } elseif ($hasExistingWatermark) {
                $disk = $existingWatermarkDisk;
                $path = $existingWatermarkPath;
            }
        }

        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk($disk)->path($path);
        $mimeType = $media->mime_type ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes((string) $media->original_name) . '"',
        ]);
    }

    public function userProjectMediaPreview(Request $request, ClientProject $project, ClientProjectMedia $media)
    {
        $this->ensureUserCanAccessProject($request, $project);

        if ((int) $media->client_project_id !== (int) $project->id) {
            abort(404);
        }

        if (!in_array((string) $media->type, ['image', 'video'], true)) {
            abort(404);
        }

        $disk = (string) $media->disk;
        $path = (string) $media->path;

        if ((string) $media->type === 'image' && !$this->projectIsPaid($project)) {
            $renderConfig = $this->resolveWatermarkRenderConfig();
            $signature = (string) ($renderConfig['signature'] ?? '');

            $existingWatermarkDisk = (string) ($media->watermark_disk ?: '');
            $existingWatermarkPath = (string) ($media->watermark_path ?: '');
            $hasExistingWatermark = $existingWatermarkDisk !== ''
                && $existingWatermarkPath !== ''
                && Storage::disk($existingWatermarkDisk)->exists($existingWatermarkPath);

            $needsRefresh = !$hasExistingWatermark || (string) ($media->watermark_signature ?? '') !== $signature;

            if ($needsRefresh) {
                $generated = $this->generateHardWatermarkVariant((string) $media->disk, (string) $media->path, $this->projectMediaBasePath($project), $renderConfig);
                if (is_array($generated) && !blank($generated['path'])) {
                    if ($hasExistingWatermark && ($existingWatermarkDisk !== (string) ($generated['disk'] ?? '') || $existingWatermarkPath !== (string) ($generated['path'] ?? ''))) {
                        Storage::disk($existingWatermarkDisk)->delete($existingWatermarkPath);
                    }

                    $media->watermark_disk = (string) ($generated['disk'] ?? (string) $media->disk);
                    $media->watermark_path = (string) ($generated['path'] ?? '');
                    $media->watermark_signature = $signature;
                    $media->save();

                    $disk = (string) $media->watermark_disk;
                    $path = (string) $media->watermark_path;
                } elseif ($hasExistingWatermark) {
                    $disk = $existingWatermarkDisk;
                    $path = $existingWatermarkPath;
                } else {
                    abort(404);
                }
            } elseif ($hasExistingWatermark) {
                $disk = $existingWatermarkDisk;
                $path = $existingWatermarkPath;
            }
        }

        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk($disk)->path($path);
        $mimeType = $media->mime_type ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes((string) $media->original_name) . '"',
        ]);
    }

    public function adminProjectMediaDestroy(Request $request, ClientProject $project, ClientProjectMedia $media): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        if ((int) $media->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $displayName = trim((string) $media->original_name) !== ''
            ? (string) $media->original_name
            : ('Media #' . $media->id);

        try {
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }

            if (!blank($media->watermark_disk) && !blank($media->watermark_path)) {
                $watermarkDisk = (string) $media->watermark_disk;
                $watermarkPath = (string) $media->watermark_path;
                if (Storage::disk($watermarkDisk)->exists($watermarkPath)) {
                    Storage::disk($watermarkDisk)->delete($watermarkPath);
                }
            }

            $media->delete();
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['media' => 'Could not delete media file. Please try again.']);
        }

        return back()->with('status', "Deleted media file: {$displayName}");
    }

    public function userProjectMediaDownload(Request $request, ClientProject $project, ClientProjectMedia $media): StreamedResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        if ((int) $media->client_project_id !== (int) $project->id) {
            abort(404);
        }

        if (!$this->projectIsPaid($project)) {
            abort(403, 'Project is not paid yet. Downloads are locked.');
        }

        if (!Storage::disk($media->disk)->exists($media->path)) {
            abort(404);
        }

        return Storage::disk($media->disk)->download($media->path, $media->original_name);
    }

    public function userProjectMediaZipDownload(Request $request, ClientProject $project): StreamedResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        if (!$this->projectIsPaid($project)) {
            abort(403, 'Project is not paid yet. Downloads are locked.');
        }

        $mediaItems = $project->media()
            ->whereIn('type', ['image', 'video'])
            ->orderBy('id')
            ->get();

        if ($mediaItems->isEmpty()) {
            abort(404, 'No gallery files found for this project.');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $zipPath = $tmpDir . '/project-' . $project->id . '-gallery-' . now()->timestamp . '-' . Str::random(6) . '.zip';
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        $usedNames = [];
        foreach ($mediaItems as $item) {
            if (!Storage::disk($item->disk)->exists($item->path)) {
                continue;
            }

            $baseName = trim((string) $item->original_name) !== '' ? $item->original_name : basename($item->path);
            $name = $baseName;
            $counter = 1;
            while (isset($usedNames[strtolower($name)])) {
                $dotPos = strrpos($baseName, '.');
                if ($dotPos === false) {
                    $name = $baseName . '-' . $counter;
                } else {
                    $name = substr($baseName, 0, $dotPos) . '-' . $counter . substr($baseName, $dotPos);
                }
                $counter++;
            }

            $usedNames[strtolower($name)] = true;
            $zip->addFromString($name, Storage::disk($item->disk)->get($item->path));
        }

        $zip->close();

        $downloadName = 'project-' . $project->id . '-gallery.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function adminClientInvoiceStore(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', 'in:draft,sent,partial,paid,overdue'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $issuedAt = $validated['issued_at'] ?? now()->toDateString();
        $dueDate = $validated['due_date'] ?? null;
        if ($dueDate !== null && strtotime((string) $dueDate) < strtotime((string) $issuedAt)) {
            return back()->withErrors(['due_date' => 'Due date cannot be earlier than issue date.'])->withInput();
        }

        $projectId = null;
        if (!blank($validated['client_project_id'] ?? null)) {
            $projectId = ClientProject::query()
                ->where('client_id', $client->id)
                ->where('id', (int) $validated['client_project_id'])
                ->value('id');
        }

        try {
            $invoice = ClientInvoice::create([
                'client_id' => $client->id,
                'client_project_id' => $projectId,
                'created_by' => $request->user()?->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => round((float) $validated['amount'], 2),
                'currency' => strtoupper(trim((string) $validated['currency'])),
                'status' => $validated['status'],
                'issued_at' => $issuedAt,
                'due_date' => $dueDate,
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->notifyClientUser(
                $client,
                'invoice_created',
                'New invoice created',
                "Invoice {$invoice->invoice_number} has been created.",
                route('user.dashboard'),
                ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]
            );
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['invoice' => 'Invoice could not be created. Please try again.'])->withInput();
        }

        return back()->with('status', 'Invoice created and ready to send.');
    }

    public function adminClientMessageStore(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $projectId = null;
        if (!blank($validated['client_project_id'] ?? null)) {
            $projectId = ClientProject::query()
                ->where('client_id', $client->id)
                ->where('id', (int) $validated['client_project_id'])
                ->value('id');
        }

        ClientMessage::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'sender_user_id' => $request->user()?->id,
            'sender_role' => 'admin',
            'message' => $validated['message'],
            'sent_at' => now(),
        ]);

        $this->notifyClientUser(
            $client,
            'new_admin_message',
            'New message from admin',
            mb_strimwidth($validated['message'], 0, 140, '...'),
            route('user.dashboard')
        );

        return back()->with('status', 'Message sent to client timeline.');
    }

    public function adminInvoiceStatusUpdate(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,partial,paid,overdue'],
        ]);

        $invoice->status = $validated['status'];
        $invoice->paid_at = $validated['status'] === 'paid' ? now() : null;
        $invoice->save();

        $invoice->loadMissing('client');
        if ($invoice->client) {
            $this->notifyClientUser(
                $invoice->client,
                'invoice_status_updated',
                'Invoice status updated',
                "Invoice {$invoice->invoice_number} is now {$invoice->status}.",
                route('user.dashboard'),
                ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number, 'status' => $invoice->status]
            );
        }

        return back()->with('status', "Invoice {$invoice->invoice_number} updated.");
    }

    public function adminServiceRequestStatusUpdate(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,accepted,in_progress,completed,closed'],
        ]);

        $serviceRequest->status = $validated['status'];
        $serviceRequest->save();

        $serviceRequest->loadMissing('client');
        if ($serviceRequest->client) {
            $this->notifyClientUser(
                $serviceRequest->client,
                'service_request_status_updated',
                'Service request updated',
                "Request \"{$serviceRequest->requested_service}\" is now {$serviceRequest->status}.",
                route('user.dashboard')
            );
        }

        return back()->with('status', 'Service request updated.');
    }

    public function adminLeadStatusUpdate(Request $request, LeadProfile $lead): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:new,qualified,contacted,won,lost,nurturing'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead->status = $validated['status'];
        if ($validated['status'] === 'qualified' && $lead->qualified_at === null) {
            $lead->qualified_at = now();
        }
        $lead->save();

        LeadEvent::create([
            'lead_profile_id' => $lead->id,
            'event_type' => 'status_updated',
            'payload' => ['status' => $lead->status, 'note' => $validated['note'] ?? null],
            'created_by' => $request->user()?->id,
        ]);

        $this->notificationService()->notifyByContact(
            $lead->email,
            $lead->phone,
            'lead_status_updated',
            'Lead status updated',
            "Your lead status is now {$lead->status}.",
            route('user.dashboard'),
            ['lead_id' => $lead->id, 'status' => $lead->status]
        );

        return back()->with('status', 'Lead status updated.');
    }

    public function adminFollowUpStore(Request $request, LeadProfile $lead): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'method' => ['required', 'in:call,email,sms'],
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $followUp = FollowUp::create([
            'lead_profile_id' => $lead->id,
            'owner_user_id' => $request->user()?->id,
            'method' => $validated['method'],
            'due_at' => $validated['due_at'],
            'status' => 'pending',
            'result_notes' => $validated['notes'] ?? null,
        ]);

        LeadEvent::create([
            'lead_profile_id' => $lead->id,
            'event_type' => 'follow_up_scheduled',
            'payload' => ['follow_up_id' => $followUp->id, 'due_at' => $followUp->due_at],
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Follow-up scheduled.');
    }

    public function adminLeadDestroy(Request $request, LeadProfile $lead): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $leadLabel = $lead->name ?: ('Lead #' . $lead->id);
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('status', "{$leadLabel} deleted successfully.");
    }

    public function adminFollowUpStatusUpdate(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $followUp->status = $validated['status'];
        if (!blank($validated['notes'] ?? null)) {
            $followUp->result_notes = $validated['notes'];
        }
        $followUp->save();

        LeadEvent::create([
            'lead_profile_id' => $followUp->lead_profile_id,
            'event_type' => 'follow_up_status_updated',
            'payload' => [
                'follow_up_id' => $followUp->id,
                'status' => $followUp->status,
                'notes' => $validated['notes'] ?? null,
            ],
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Follow-up status updated.');
    }

    public function userDashboard(Request $request): View
    {
        $user = $request->user();

        $leads = LeadProfile::query()
            ->with('conversation:id,status,last_message_at')
            ->where(function ($query) use ($user): void {
                $query->where('email', $user->email);
                if ($user->phone) {
                    $query->orWhere('phone', $user->phone);
                }
            })
            ->latest('id')
            ->paginate(10);

        $quotes = QuoteBuild::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('options->contact_email', $user->email);
                if ($user->phone) {
                    $query->orWhere('options->contact_phone', $user->phone);
                }
            })
            ->latest('id')
            ->limit(20)
            ->get(['id', 'quote_id', 'status', 'estimated_total', 'currency', 'submitted_at', 'services']);

        $client = Client::query()
            ->with([
                'projects' => function ($query): void {
                    $query->latest('id')->limit(8)->with([
                        'media' => function ($mediaQuery): void {
                            $mediaQuery->latest('id');
                        },
                        'invoices:id,client_project_id,status',
                    ]);
                },
                'invoices' => function ($query): void {
                    $query->latest('id')->limit(8);
                },
                'messages' => function ($query): void {
                    $query->latest('id')->limit(8);
                },
                'serviceRequests' => function ($query): void {
                    $query->latest('id')->limit(10);
                },
            ])
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
                if (!blank($user->phone)) {
                    $query->orWhere('phone', $user->phone);
                }
            })
            ->latest('id')
            ->first();

        $galleryPayloadByProject = $client
            ? $this->buildProjectGalleryPayloadMap($client->projects, true, false, true)
            : [];

        return view('user.dashboard', [
            'leads' => $leads,
            'quotes' => $quotes,
            'client' => $client,
            'projectStatuses' => ['accepted', 'shooting', 'editing', 'complete'],
            'galleryPayloadByProject' => $galleryPayloadByProject,
        ]);
    }

    public function userServiceRequestStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'requested_service' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        $client = Client::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$client) {
            $client = Client::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => 'active',
                'notes' => 'Auto-created from client portal service request.',
            ]);
        }

        ClientServiceRequest::create([
            'client_id' => $client->id,
            'requester_user_id' => $user->id,
            'requested_service' => $validated['requested_service'],
            'subject' => $validated['subject'] ?? null,
            'details' => $validated['details'] ?? null,
            'preferred_date' => $validated['preferred_date'] ?? null,
            'status' => 'new',
        ]);

        ClientMessage::create([
            'client_id' => $client->id,
            'sender_user_id' => $user->id,
            'sender_role' => 'client',
            'message' => 'New service request submitted: ' . $validated['requested_service'] . ($validated['subject'] ? ' - ' . $validated['subject'] : ''),
            'sent_at' => now(),
        ]);

        $this->notificationService()->notifyInternal(
            'new_service_request',
            'New client service request',
            "{$user->name} submitted: {$validated['requested_service']}.",
            route('admin.clients.index')
        );

        return back()->with('status', 'Service request submitted successfully.');
    }

    public function adminQuoteShow(QuoteBuild $quote): View
    {
        $quote->load([
            'leadProfile:id,name,email,phone,service_type,property_type,status',
            'events.creator:id,name,email',
        ]);

        return view('admin.quote-show', [
            'quote' => $quote,
        ]);
    }

    public function adminQuoteStatusUpdate(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:new,reviewed,contacted,booked,lost'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $quote->status = $validated['status'];
        $quote->save();

        QuoteEvent::create([
            'quote_build_id' => $quote->id,
            'event_type' => 'status_updated',
            'payload' => [
                'status' => $quote->status,
                'note' => $validated['note'] ?? null,
            ],
            'created_by' => $request->user()?->id,
        ]);

        $this->notifyQuoteContact(
            $quote,
            'quote_status_updated',
            'Quote status updated',
            "Quote {$quote->quote_id} is now {$quote->status}.",
            route('user.quotes.show', $quote),
            ['quote_id' => $quote->id, 'status' => $quote->status]
        );

        return back()->with('status', 'Quote status updated.');
    }

    public function adminQuoteResendEmail(Request $request, QuoteBuild $quote, QuoteNotificationService $quoteNotificationService): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $quoteNotificationService->sendSubmissionEmails($quote);

        QuoteEvent::create([
            'quote_build_id' => $quote->id,
            'event_type' => 'email_resent',
            'payload' => ['by' => 'admin_manual_resend'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Quote emails resent.');
    }

    public function adminQuoteDestroy(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $quoteId = $quote->quote_id;
        $quote->delete();

        return redirect()->route('admin.quotes.index')->with('status', "Quote {$quoteId} deleted successfully. Client can submit a new request.");
    }

    public function adminQuoteLineItemsUpdate(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $packageCode = strtolower((string) data_get($quote->options, 'package_code', ''));
        if (in_array($packageCode, ['essential', 'signature', 'prestige'], true)) {
            return back()->withErrors(['line_items' => 'Fixed package quotes are locked for editing.']);
        }

        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.label' => ['required', 'string', 'max:150'],
            'line_items.*.amount' => ['required', 'integer', 'min:0', 'max:99999999'],
        ]);

        $lineItems = collect($validated['line_items'])
            ->map(static function (array $item): array {
                return [
                    'label' => trim((string) ($item['label'] ?? '')),
                    'amount' => (int) ($item['amount'] ?? 0),
                ];
            })
            ->filter(static fn (array $item): bool => $item['label'] !== '')
            ->values()
            ->all();

        if (count($lineItems) === 0) {
            return back()->withErrors(['line_items' => 'Please keep at least one line item.'])->withInput();
        }

        $oldTotal = (int) $quote->estimated_total;
        $newTotal = (int) collect($lineItems)->sum('amount');

        $quote->line_items = $lineItems;
        $quote->estimated_total = $newTotal;
        if (isset($validated['currency']) && trim((string) $validated['currency']) !== '') {
            $quote->currency = strtoupper(trim((string) $validated['currency']));
        }
        if (array_key_exists('notes', $validated)) {
            $quote->notes = $validated['notes'];
        }
        $quote->save();

        QuoteEvent::create([
            'quote_build_id' => $quote->id,
            'event_type' => 'line_items_updated',
            'payload' => [
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'line_item_count' => count($lineItems),
            ],
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Quote line items updated.');
    }

    public function userQuoteShow(Request $request, QuoteBuild $quote): View
    {
        $user = $request->user();
        $email = (string) data_get($quote->options, 'contact_email', '');
        $phone = (string) data_get($quote->options, 'contact_phone', '');

        $allowed = $quote->user_id === $user->id
            || ($email !== '' && $email === $user->email)
            || ($phone !== '' && filled($user->phone) && $phone === $user->phone);

        abort_unless($allowed, 403);

        $quote->load([
            'events.creator:id,name,email',
        ]);

        return view('user.quote-show', [
            'quote' => $quote,
        ]);
    }

    public function userQuoteRevisionRequest(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $user = $request->user();
        $email = (string) data_get($quote->options, 'contact_email', '');
        $phone = (string) data_get($quote->options, 'contact_phone', '');

        $allowed = $quote->user_id === $user->id
            || ($email !== '' && $email === $user->email)
            || ($phone !== '' && filled($user->phone) && $phone === $user->phone);

        abort_unless($allowed, 403);

        $validated = $request->validate([
            'revision_note' => ['required', 'string', 'max:1000'],
            'preferred_contact' => ['nullable', 'in:email,phone,call'],
        ]);

        QuoteEvent::create([
            'quote_build_id' => $quote->id,
            'event_type' => 'revision_requested',
            'payload' => [
                'message' => $validated['revision_note'],
                'preferred_contact' => $validated['preferred_contact'] ?? null,
            ],
            'created_by' => $user?->id,
        ]);

        if (in_array($quote->status, ['new', 'reviewed'], true)) {
            $quote->status = 'reviewed';
            $quote->save();
        }

        $this->notificationService()->notifyInternal(
            'quote_revision_requested',
            'Quote revision requested',
            "{$user->name} requested a revision for {$quote->quote_id}.",
            route('admin.quotes.show', $quote),
            ['quote_id' => $quote->id]
        );

        return back()->with('status', 'Revision request sent to admin team.');
    }

    public function notificationsRead(Request $request, PanelNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()?->id, 403);

        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return back();
    }

    public function notificationsReadAjax(Request $request, PanelNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()?->id, 403);

        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        $userId = (int) $request->user()?->id;
        $unreadCount = PanelNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    public function notificationsReadAll(Request $request): RedirectResponse
    {
        PanelNotification::query()
            ->where('user_id', (int) $request->user()?->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function notificationsReadAllAjax(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->id;

        PanelNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }

    public function notificationsFeed(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->id;

        $items = PanelNotification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(20)
            ->get();

        $unreadCount = PanelNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $notifications = $items->map(function (PanelNotification $item): array {
            return [
                'id' => (int) $item->id,
                'type' => (string) $item->type,
                'title' => (string) $item->title,
                'body' => (string) ($item->body ?? ''),
                'action_url' => (string) ($item->action_url ?? ''),
                'is_unread' => $item->read_at === null,
                'created_human' => (string) ($item->created_at?->diffForHumans() ?? ''),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function notifyClientUser(Client $client, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        if ($client->user_id) {
            $this->notificationService()->notifyUser((int) $client->user_id, $type, $title, $body, $actionUrl, $data);
            return;
        }

        $this->notificationService()->notifyByContact($client->email, $client->phone, $type, $title, $body, $actionUrl, $data);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function notifyQuoteContact(QuoteBuild $quote, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        if ($quote->user_id) {
            $this->notificationService()->notifyUser((int) $quote->user_id, $type, $title, $body, $actionUrl, $data);
            return;
        }

        $this->notificationService()->notifyByContact(
            (string) data_get($quote->options, 'contact_email', ''),
            (string) data_get($quote->options, 'contact_phone', ''),
            $type,
            $title,
            $body,
            $actionUrl,
            $data
        );
    }

    private function notificationService(): PanelNotificationService
    {
        return app(PanelNotificationService::class);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function createEmailLogEntry(array $payload): ?EmailLog
    {
        try {
            return EmailLog::create([
                'created_by' => $payload['created_by'] ?? null,
                'mode' => (string) ($payload['mode'] ?? 'custom'),
                'template_key' => blank($payload['template_key'] ?? null) ? null : (string) $payload['template_key'],
                'recipient_email' => (string) ($payload['recipient_email'] ?? ''),
                'reply_to' => blank($payload['reply_to'] ?? null) ? null : (string) $payload['reply_to'],
                'cc' => blank($payload['cc'] ?? null) ? null : (string) $payload['cc'],
                'bcc' => blank($payload['bcc'] ?? null) ? null : (string) $payload['bcc'],
                'subject' => (string) ($payload['subject'] ?? ''),
                'body_preview' => blank($payload['body_preview'] ?? null) ? null : (string) $payload['body_preview'],
                'status' => (string) ($payload['status'] ?? 'sent'),
                'error_message' => blank($payload['error_message'] ?? null) ? null : (string) $payload['error_message'],
                'sent_at' => $payload['sent_at'] ?? null,
                'provider_message_id' => blank($payload['provider_message_id'] ?? null) ? null : (string) $payload['provider_message_id'],
                'provider_status' => blank($payload['provider_status'] ?? null) ? null : (string) $payload['provider_status'],
                'provider_last_event_at' => $payload['provider_last_event_at'] ?? null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    /**
     * @return array{subject:string,body:string}|null
     */
    private function buildAdminEmailTemplate(string $templateKey): ?array
    {
        $templateKey = strtolower(trim($templateKey));

        if ($templateKey === 'delivery_test') {
            return [
                'subject' => 'Maccento CRM Delivery Test [' . now()->format('Y-m-d H:i') . ']',
                'body' => implode("\n", [
                    'This is a one-click delivery test from Maccento CRM.',
                    '',
                    'Mail transport is active and this inbox is receiving notifications.',
                    'Environment: ' . config('app.env'),
                    'Timestamp: ' . now()->toDateTimeString(),
                ]),
            ];
        }

        if ($templateKey === 'pipeline_snapshot') {
            $newLeads = LeadProfile::query()->where('status', 'new')->count();
            $qualifiedLeads = LeadProfile::query()->where('status', 'qualified')->count();
            $newQuotes = QuoteBuild::query()->where('status', 'new')->count();
            $bookedQuotes = QuoteBuild::query()->where('status', 'booked')->count();
            $overdueInvoices = ClientInvoice::query()
                ->where('status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count();

            return [
                'subject' => 'Maccento Pipeline Snapshot - ' . now()->format('Y-m-d'),
                'body' => implode("\n", [
                    'Pipeline summary generated from CRM:',
                    '',
                    "New leads: {$newLeads}",
                    "Qualified leads: {$qualifiedLeads}",
                    "New quotes: {$newQuotes}",
                    "Booked quotes: {$bookedQuotes}",
                    "Overdue invoices: {$overdueInvoices}",
                    '',
                    'Review the CRM dashboard for detailed records and action items.',
                ]),
            ];
        }

        if ($templateKey === 'followup_digest') {
            $dueFollowUps = FollowUp::query()
                ->where('status', 'pending')
                ->whereDate('due_at', '<=', now()->toDateString())
                ->count();
            $reviewedQuotes = QuoteBuild::query()->where('status', 'reviewed')->count();
            $contactedQuotes = QuoteBuild::query()->where('status', 'contacted')->count();

            return [
                'subject' => 'Maccento Follow-up Digest - ' . now()->format('Y-m-d'),
                'body' => implode("\n", [
                    'Operational follow-up digest:',
                    '',
                    "Pending follow-ups due: {$dueFollowUps}",
                    "Quotes in reviewed stage: {$reviewedQuotes}",
                    "Quotes in contacted stage: {$contactedQuotes}",
                    '',
                    'Please prioritize overdue follow-ups first, then reviewed quotes.',
                ]),
            ];
        }

        return null;
    }

    /**
     * @return array{valid:array<int,string>,invalid:array<int,string>}
     */
    private function parseEmailList(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['valid' => [], 'invalid' => []];
        }

        $items = preg_split('/[,;\s]+/', $value) ?: [];
        $valid = [];
        $invalid = [];

        foreach ($items as $item) {
            $email = trim((string) $item);
            if ($email === '') {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $email;
                continue;
            }

            $invalid[] = $email;
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => array_values(array_unique($invalid)),
        ];
    }

    private function resolveOutboundThreadProjectId(string $recipientEmail, ?int $requestedProjectId = null): ?int
    {
        if ($requestedProjectId !== null && $requestedProjectId > 0) {
            $projectId = ClientProject::query()->where('id', $requestedProjectId)->value('id');
            return $projectId !== null ? (int) $projectId : null;
        }

        $normalizedEmail = Str::lower(trim($recipientEmail));
        if ($normalizedEmail === '') {
            return null;
        }

        $clientId = Client::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->value('id');

        if ($clientId === null) {
            return null;
        }

        // Auto-map only when the client has a single project to avoid ambiguous threading.
        $projectIds = ClientProject::query()
            ->where('client_id', (int) $clientId)
            ->orderByDesc('id')
            ->limit(2)
            ->pluck('id')
            ->all();

        if (count($projectIds) !== 1) {
            return null;
        }

        return (int) $projectIds[0];
    }

    private function appendProjectThreadTag(string $subject, int $projectId): string
    {
        $trimmed = trim($subject);
        if ($trimmed === '' || $projectId <= 0) {
            return $trimmed;
        }

        if (preg_match('/\[(?:project|proj|p)\s*[-:#]?\s*\d+\]/i', $trimmed) === 1) {
            return $trimmed;
        }

        return $trimmed . " [P#{$projectId}]";
    }

    /**
     * @return array<int,string>
     */
    private function emailBodyToLines(string $body): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $body) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }

    private function csvSafe(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $first = substr($value, 0, 1);
        if (in_array($first, ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function isOwnerRole(string $role): bool
    {
        $role = strtolower(trim($role));
        return in_array($role, ['owner', 'admin'], true);
    }

    private function isManagerRole(string $role): bool
    {
        return strtolower(trim($role)) === 'manager';
    }

    private function canExportData(Request $request): bool
    {
        return !$this->isManagerRole((string) $request->user()?->role);
    }

    private function ensurePipelineWriteAccess(Request $request): void
    {
        $role = strtolower(trim((string) $request->user()?->role));
        abort_unless(in_array($role, ['owner', 'admin', 'manager'], true), 403);
    }

    private function ensureOwnerAdminAccess(Request $request): void
    {
        $role = strtolower(trim((string) $request->user()?->role));
        abort_unless(in_array($role, ['owner', 'admin'], true), 403);
    }

    private function ensureUserCanAccessProject(Request $request, ClientProject $project): void
    {
        $role = strtolower(trim((string) $request->user()?->role));
        if (in_array($role, ['owner', 'admin', 'manager', 'photographer', 'editor'], true)) {
            return;
        }

        $user = $request->user();
        $project->loadMissing('client:id,user_id,email,phone');
        $client = $project->client;

        $allowed = $client !== null
            && (
                (int) ($client->user_id ?? 0) === (int) ($user?->id ?? 0)
                || (!blank($client->email) && !blank($user?->email) && strcasecmp((string) $client->email, (string) $user->email) === 0)
                || (!blank($client->phone) && !blank($user?->phone) && (string) $client->phone === (string) $user->phone)
            );

        abort_unless($allowed, 403);
    }

    private function projectIsPaid(ClientProject $project): bool
    {
        return $project->invoices()->where('status', 'paid')->exists();
    }

    /**
     * @param iterable<ClientProject> $projects
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function buildProjectGalleryPayloadMap(iterable $projects, bool $useWatermarkPreview, bool $useAdminViewRoute = false, bool $useUserPreviewRoute = false): array
    {
        $payload = [];

        foreach ($projects as $project) {
            if (!$project instanceof ClientProject) {
                continue;
            }

            $payload[(int) $project->id] = $this->buildProjectGalleryPayload($project, $useWatermarkPreview, $useAdminViewRoute, $useUserPreviewRoute);
        }

        return $payload;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildProjectGalleryPayload(ClientProject $project, bool $useWatermarkPreview, bool $useAdminViewRoute = false, bool $useUserPreviewRoute = false): array
    {
        $project->loadMissing('media', 'invoices:id,client_project_id,status');
        $isPaid = $project->invoices->contains(static fn (ClientInvoice $invoice): bool => $invoice->status === 'paid');

        return $project->media
            ->whereIn('type', ['image', 'video'])
            ->values()
            ->map(function (ClientProjectMedia $item) use ($useWatermarkPreview, $isPaid, $useAdminViewRoute, $useUserPreviewRoute, $project): array {
                $previewMode = $useWatermarkPreview && !$isPaid;
                $disk = $item->disk;
                $path = $item->path;

                if ($previewMode && $item->type === 'image' && !blank($item->watermark_disk) && !blank($item->watermark_path)) {
                    $disk = (string) $item->watermark_disk;
                    $path = (string) $item->watermark_path;
                }

                if ($useAdminViewRoute) {
                    $url = route('admin.projects.media.view', ['project' => $project, 'media' => $item]);
                } elseif ($useUserPreviewRoute) {
                    $url = route('user.projects.media.preview', ['project' => $project, 'media' => $item]);
                } else {
                    $url = Storage::disk($disk)->url($path);
                }

                return [
                    'id' => (int) $item->id,
                    'name' => (string) $item->original_name,
                    'type' => (string) $item->type,
                    'mime' => (string) ($item->mime_type ?? ''),
                    'url' => $url,
                ];
            })
            ->all();
    }

    private function getWatermarkSettings(): WatermarkSetting
    {
        $settings = WatermarkSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return WatermarkSetting::query()->create([
            'logo_disk' => null,
            'logo_path' => null,
            'position' => 'center',
            'size' => 'medium',
            'opacity_percent' => 62,
        ]);
    }

    /**
     * @return array{logo_absolute_path:?string,position:string,size:string,opacity_percent:int,signature:string}
     */
    private function resolveWatermarkRenderConfig(?WatermarkSetting $settings = null): array
    {
        $settings ??= $this->getWatermarkSettings();

        $position = (string) ($settings->position ?: 'center');
        if (!in_array($position, ['top_left', 'top_right', 'bottom_left', 'bottom_right', 'center'], true)) {
            $position = 'center';
        }

        $size = (string) ($settings->size ?: 'medium');
        if (!in_array($size, ['small', 'medium', 'large'], true)) {
            $size = 'medium';
        }

        $opacityPercent = (int) ($settings->opacity_percent ?? 62);
        if ($opacityPercent < 1) {
            $opacityPercent = 1;
        }
        if ($opacityPercent > 100) {
            $opacityPercent = 100;
        }

        $logoDisk = (string) ($settings->logo_disk ?: 'public');
        $logoPath = (string) ($settings->logo_path ?? '');
        $logoAbsolutePath = null;
        if ($logoPath !== '' && Storage::disk($logoDisk)->exists($logoPath)) {
            $logoAbsolutePath = Storage::disk($logoDisk)->path($logoPath);
        }

        $signature = hash('sha256', json_encode([
            'logo_disk' => $logoDisk,
            'logo_path' => $logoPath,
            'position' => $position,
            'size' => $size,
            'opacity_percent' => $opacityPercent,
        ]));

        return [
            'logo_absolute_path' => $logoAbsolutePath,
            'position' => $position,
            'size' => $size,
            'opacity_percent' => $opacityPercent,
            'signature' => $signature,
        ];
    }

    private function watermarkScaleFromSize(string $size): float
    {
        if ($size === 'small') {
            return 0.16;
        }

        if ($size === 'large') {
            return 0.34;
        }

        return 0.24;
    }

    private function projectMediaBasePath(ClientProject $project): string
    {
        $projectTitle = trim((string) ($project->title ?? ''));
        $slug = Str::slug($projectTitle);
        if ($slug === '') {
            $slug = 'project';
        }

        return 'media/' . $slug . '-' . (int) $project->id;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveWatermarkCoordinates(int $canvasWidth, int $canvasHeight, int $markWidth, int $markHeight, string $position): array
    {
        $margin = max(12, (int) round(min($canvasWidth, $canvasHeight) * 0.03));

        if ($position === 'top_left') {
            return [$margin, $margin];
        }

        if ($position === 'top_right') {
            return [max($margin, $canvasWidth - $markWidth - $margin), $margin];
        }

        if ($position === 'bottom_left') {
            return [$margin, max($margin, $canvasHeight - $markHeight - $margin)];
        }

        if ($position === 'bottom_right') {
            return [
                max($margin, $canvasWidth - $markWidth - $margin),
                max($margin, $canvasHeight - $markHeight - $margin),
            ];
        }

        return [
            (int) max($margin, floor(($canvasWidth - $markWidth) / 2)),
            (int) max($margin, floor(($canvasHeight - $markHeight) / 2)),
        ];
    }

    /**
    * @param array{logo_absolute_path:?string,position:string,size:string,opacity_percent:int,signature:string} $renderConfig
     * @return array{disk:string,path:string}|null
     */
    private function generateHardWatermarkVariant(string $disk, string $originalPath, string $projectMediaBasePath, array $renderConfig): ?array
    {
        try {
            if (!Storage::disk($disk)->exists($originalPath)) {
                return null;
            }

            $absoluteSourcePath = Storage::disk($disk)->path($originalPath);
            $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            if ($extension === '') {
                $extension = 'jpg';
            }

            $targetPath = trim($projectMediaBasePath, '/') . '/gallery-watermarked/' . Str::random(20) . '.' . $extension;
            $absoluteTargetPath = Storage::disk($disk)->path($targetPath);
            $targetDir = dirname($absoluteTargetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            $generated = false;

            if (class_exists('Imagick')) {
                $generated = $this->generateWatermarkViaImagick($absoluteSourcePath, $absoluteTargetPath, $renderConfig);
            }

            if (!$generated && extension_loaded('gd')) {
                $generated = $this->generateWatermarkViaGd($absoluteSourcePath, $absoluteTargetPath, $renderConfig);
            }

            if (!$generated || !file_exists($absoluteTargetPath)) {
                return null;
            }

            return [
                'disk' => $disk,
                'path' => $targetPath,
            ];
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    /**
    * @param array{logo_absolute_path:?string,position:string,size:string,opacity_percent:int,signature:string} $renderConfig
     */
    private function generateWatermarkViaImagick(string $sourcePath, string $targetPath, array $renderConfig): bool
    {
        try {
            $image = new \Imagick($sourcePath);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            $logoPath = (string) ($renderConfig['logo_absolute_path'] ?? '');
            $position = (string) ($renderConfig['position'] ?? 'center');
            $size = (string) ($renderConfig['size'] ?? 'medium');
            $opacityPercent = (int) ($renderConfig['opacity_percent'] ?? 62);
            $opacityPercent = max(1, min(100, $opacityPercent));

            if ($logoPath !== '' && is_file($logoPath)) {
                $logo = new \Imagick($logoPath);
                $scale = $this->watermarkScaleFromSize($size);
                $targetWidth = max(56, (int) round(min($width, $height) * $scale));
                $logo->resizeImage($targetWidth, 0, \Imagick::FILTER_LANCZOS, 1, true);
                $logo->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                $logo->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $opacityPercent / 100, \Imagick::CHANNEL_ALPHA);

                [$x, $y] = $this->resolveWatermarkCoordinates($width, $height, $logo->getImageWidth(), $logo->getImageHeight(), $position);
                $image->compositeImage($logo, \Imagick::COMPOSITE_OVER, $x, $y);

                $logo->clear();
                $logo->destroy();
            } else {
                $draw = new \ImagickDraw();
                $draw->setFillColor(new \ImagickPixel('rgba(255,255,255,0.22)'));
                $draw->setGravity(\Imagick::GRAVITY_CENTER);
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
                $draw->setFontSize((float) max(22, (int) round(min($width, $height) / 9)));
                $image->annotateImage($draw, 0, 0, 0, 'MACCENTO PREVIEW');
            }

            $result = $image->writeImage($targetPath);
            $image->clear();
            $image->destroy();

            return (bool) $result;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

    /**
    * @param array{logo_absolute_path:?string,position:string,size:string,opacity_percent:int,signature:string} $renderConfig
     */
    private function generateWatermarkViaGd(string $sourcePath, string $targetPath, array $renderConfig): bool
    {
        $imageInfo = @getimagesize($sourcePath);
        $mimeType = (string) ($imageInfo['mime'] ?? '');

        $image = null;
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
            $image = @imagecreatefromjpeg($sourcePath);
        } elseif ($mimeType === 'image/png') {
            $image = @imagecreatefrompng($sourcePath);
        } elseif ($mimeType === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($sourcePath);
        } elseif ($mimeType === 'image/gif') {
            $image = @imagecreatefromgif($sourcePath);
        }

        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $logoPath = (string) ($renderConfig['logo_absolute_path'] ?? '');
        $position = (string) ($renderConfig['position'] ?? 'center');
        $size = (string) ($renderConfig['size'] ?? 'medium');
        $opacityPercent = (int) ($renderConfig['opacity_percent'] ?? 62);
        $opacityPercent = max(1, min(100, $opacityPercent));

        if ($logoPath !== '' && is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                imagealphablending($logo, true);
                imagesavealpha($logo, true);

                $logoWidth = imagesx($logo);
                $logoHeight = imagesy($logo);
                $scale = $this->watermarkScaleFromSize($size);
                $targetWidth = max(56, (int) round(min($width, $height) * $scale));
                $targetHeight = max(32, (int) round(($logoHeight / max(1, $logoWidth)) * $targetWidth));

                $resizedLogo = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($resizedLogo, false);
                imagesavealpha($resizedLogo, true);
                $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
                imagefilledrectangle($resizedLogo, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $targetWidth, $targetHeight, $logoWidth, $logoHeight);

                if (function_exists('imagefilter')) {
                    $alphaAmount = 100 - $opacityPercent;
                    if ($alphaAmount > 0) {
                        imagefilter($resizedLogo, IMG_FILTER_COLORIZE, 0, 0, 0, $alphaAmount);
                    }
                }

                [$x, $y] = $this->resolveWatermarkCoordinates($width, $height, $targetWidth, $targetHeight, $position);
                imagecopy($image, $resizedLogo, $x, $y, 0, 0, $targetWidth, $targetHeight);

                imagedestroy($resizedLogo);
                imagedestroy($logo);
            }
        } else {
            $color = imagecolorallocatealpha($image, 255, 255, 255, 72);
            $label = 'MACCENTO PREVIEW';
            $font = 5;
            $stepX = max(140, (int) floor($width / 3));
            $stepY = max(90, (int) floor($height / 3));

            for ($y = 12; $y < $height; $y += $stepY) {
                for ($x = 12; $x < $width; $x += $stepX) {
                    imagestring($image, $font, $x, $y, $label, $color);
                }
            }
        }

        $saved = false;
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
            $saved = imagejpeg($image, $targetPath, 85);
        } elseif ($mimeType === 'image/png') {
            $saved = imagepng($image, $targetPath, 6);
        } elseif ($mimeType === 'image/webp' && function_exists('imagewebp')) {
            $saved = imagewebp($image, $targetPath, 85);
        } elseif ($mimeType === 'image/gif') {
            $saved = imagegif($image, $targetPath);
        }

        imagedestroy($image);
        return (bool) $saved;
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function extractDateRange(Request $request, string $fromKey, string $toKey): array
    {
        $from = $this->normalizeDate((string) $request->input($fromKey, ''));
        $to = $this->normalizeDate((string) $request->input($toKey, ''));

        if ($from !== null && $to !== null && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'INV-' . $date . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            if (!ClientInvoice::query()->where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'INV-' . $date . '-' . strtoupper(uniqid());
    }
}
