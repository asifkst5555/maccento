<?php

namespace App\Http\Controllers;

use App\Mail\BrandedNotificationMail;
use App\Models\AiUsageLog;
use App\Models\ApiIntegrationSetting;
use App\Models\BookingRequest;
use App\Models\BackupSetting;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientInvoicePayment;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientProjectAssignment;
use App\Models\ClientProjectComment;
use App\Models\ClientProjectMedia;
use App\Models\ProjectTask;
use App\Models\ClientServiceRequest;
use App\Models\CurrencySetting;
use App\Models\EmailDraft;
use App\Models\EmailLog;
use App\Models\FollowUp;
use App\Models\InboundEmail;
use App\Models\RequestEditLog;
use App\Models\InvoiceSetting;
use App\Models\LeadAutoEmailSetting;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\PanelNotification;
use App\Models\QuoteEvent;
use App\Models\QuoteBuild;
use App\Models\SendgridWebhookEvent;
use App\Models\User;
use App\Models\UserMessage;
use App\Models\WatermarkSetting;
use App\Models\WebsiteFormSubmission;
use App\Services\PanelNotificationService;
use App\Services\QuoteNotificationService;
use App\Services\LeadAutoCaptureService;
use App\Services\InvoiceEmailService;
use App\Services\OutboundWebhookService;
use App\Services\AI\AiProviderManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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

        $currencyOptions = $this->currencyOptions();
        $defaultCurrency = $this->resolveDefaultCurrency();

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
            'currencyOptions' => $currencyOptions,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    public function adminProjectsIndex(Request $request): View
    {
        $display = 'table';

        $scope = trim((string) $request->string('project_scope'));
        if (!in_array($scope, ['ongoing', 'past', 'all'], true)) {
            $scope = 'ongoing';
        }

        $status = trim((string) $request->string('project_status'));
        $search = trim((string) $request->string('project_search'));
        $allowedStatuses = ['accepted', 'shooting', 'editing', 'complete'];
        $projectAction = trim((string) $request->string('project_action'));
        if (!in_array($projectAction, ['create', ''], true)) {
            $projectAction = '';
        }

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
        $canDeleteProjects = $this->isOwnerRole((string) $request->user()?->role);
        $projectClients = Client::query()
            ->select(['id', 'name', 'email', 'status'])
            ->orderBy('name')
            ->limit(400)
            ->get();

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
                'project_action' => $projectAction,
            ],
            'projectStatuses' => $allowedStatuses,
            'canManageProjects' => $canManageProjects,
            'canDeleteProjects' => $canDeleteProjects,
            'projectClients' => $projectClients,
            'assignableUsers' => $this->assignableProjectUsers(),
        ]);
    }

    public function adminProjectStore(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:120'],
            'property_address' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', 'in:accepted,shooting,editing,complete'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if (!blank($validated['scheduled_at'] ?? null) && !blank($validated['due_at'] ?? null)) {
            if (strtotime((string) $validated['due_at']) < strtotime((string) $validated['scheduled_at'])) {
                return back()->withErrors(['due_at' => 'Due date/time cannot be earlier than schedule.'])->withInput();
            }
        }

        $project = ClientProject::create([
            'client_id' => (int) $validated['client_id'],
            'created_by' => $request->user()?->id,
            'title' => $validated['title'],
            'service_type' => $validated['service_type'] ?? null,
            'property_address' => $validated['property_address'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $assignmentIds = $this->sanitizeAssignableUserIds((array) ($validated['assigned_user_ids'] ?? []));
        if ($assignmentIds !== []) {
            $assignmentRows = array_map(fn (int $userId): array => [
                'client_project_id' => $project->id,
                'user_id' => $userId,
                'assigned_by' => (int) ($request->user()?->id ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ], $assignmentIds);

            ClientProjectAssignment::query()->insert($assignmentRows);
        }

        $internalActionUrl = $this->projectInternalActionUrl($project);
        $this->notifyProjectAssignees(
            $project,
            'project_assigned',
            'New project assigned',
            "Project \"{$project->title}\" has been created and assigned.",
            $internalActionUrl,
            ['project_id' => $project->id, 'client_id' => $project->client_id],
            (int) ($request->user()?->id ?? 0),
            true
        );

        $client = Client::query()->find((int) $project->client_id);
        if ($client) {
            $this->notifyClientUser(
                $client,
                'project_created',
                'New project created',
                "Your project \"{$project->title}\" has been created.",
                route('user.projects.show', $project),
                ['project_id' => $project->id]
            );
        }

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'create',
            'Project created: ' . ($project->title ?: ('Project #' . $project->id)),
            [
                'after' => [
                    'title' => $project->title,
                    'status' => $project->status,
                    'scheduled_at' => $project->scheduled_at,
                    'due_at' => $project->due_at,
                    'client_id' => $project->client_id,
                ],
            ]
        );

        return redirect()
            ->route('admin.clients.show', ['client' => (int) $validated['client_id'], 'project_id' => $project->id])
            ->with('status', 'Project created successfully.');
    }

    public function adminProjectDestroy(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $snapshot = $project->only(['title', 'status', 'scheduled_at', 'due_at', 'client_id']);
        $project->delete();

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'delete',
            'Project deleted: ' . ($project->title ?: ('Project #' . $project->id)),
            ['before' => $snapshot]
        );

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project deleted successfully.');
    }

    public function adminProjectWorkspace(Request $request, ClientProject $project): View
    {
        $this->ensureInternalUserCanAccessAssignedProject($request, $project);

        $project->loadMissing([
            'client:id,name,email,phone',
            'assignments.user:id,name,email,role',
            'invoices:id,client_project_id,invoice_number,amount,currency,status,due_date,paid_at',
            'tasks.assignee:id,name,email,role',
            'tasks.creator:id,name,email,role',
            'comments' => function ($query): void {
                $query->latest('id');
            },
            'comments.user:id,name,email,role',
            'comments.parent.user:id,name,email,role',
        ]);

        $role = strtolower(trim((string) $request->user()?->role));
        $canManageProjects = in_array($role, ['owner', 'admin', 'manager'], true);
        $currencyOptions = $this->currencyOptions();
        $defaultCurrency = $this->resolveDefaultCurrency();

        return view('admin.project-workspace', [
            'project' => $project,
            'assignableUsers' => $this->assignableProjectUsers(),
            'canManageProjects' => $canManageProjects,
            'currencyOptions' => $currencyOptions,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    public function adminProjectCalendarIcs(Request $request, ClientProject $project)
    {
        $this->ensureInternalUserCanAccessAssignedProject($request, $project);

        $startAt = $project->scheduled_at ?: $project->due_at;
        if (!$startAt) {
            abort(404, 'No scheduled date is available for this project.');
        }

        $endAt = (clone $startAt)->addHours(2);
        $uid = 'project-' . $project->id . '@maccento.local';
        $title = $project->title ?: 'Project';
        $clientName = $project->client?->name ?: 'Client';

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Maccento CRM//Project Calendar//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $startAt->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $endAt->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . addslashes($title),
            'DESCRIPTION:' . addslashes("Client: {$clientName}"),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="project-' . $project->id . '.ics"',
        ]);
    }

    public function adminMediaDeliveryIndex(Request $request): View
    {
        $search = trim((string) $request->string('media_search'));
        $role = strtolower(trim((string) $request->user()?->role));
        $userId = (int) ($request->user()?->id ?? 0);

        $projects = ClientProject::query()
            ->with([
                'client:id,name,email,phone',
                'media' => function ($query): void {
                    $query->latest('id')->with('uploader:id,name,email,role');
                },
                'assignments.user:id,name,email,role',
                'invoices:id,client_project_id,status',
                'comments.user:id,name,email,role',
                'comments.parent.user:id,name,email,role',
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
            ->when(in_array($role, ['photographer', 'editor'], true), function ($query) use ($userId): void {
                $query->whereHas('assignments', function ($assignmentQuery) use ($userId): void {
                    $assignmentQuery->where('user_id', $userId);
                });
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $canUploadMedia = in_array($role, ['owner', 'admin', 'manager', 'photographer', 'editor'], true);
        $canDeleteMedia = in_array($role, ['owner', 'admin', 'manager'], true);
        $isScopedMediaUser = in_array($role, ['photographer', 'editor'], true);
        $canViewInvoices = in_array($role, ['owner', 'admin', 'manager'], true);
        $galleryPayloadByProject = $this->buildProjectGalleryPayloadMap($projects->getCollection(), false, true, false);

        return view('admin.media-delivery-index', [
            'projects' => $projects,
            'filters' => [
                'media_search' => $search,
            ],
            'canUploadMedia' => $canUploadMedia,
            'canDeleteMedia' => $canDeleteMedia,
            'isScopedMediaUser' => $isScopedMediaUser,
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
        $invoiceSettings = $this->resolveInvoiceSettings();

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
            'invoiceSettings' => $invoiceSettings,
        ]);
    }

    public function adminInvoiceSettingsUpdate(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'include_tax_on_pdf' => ['nullable', 'in:0,1'],
            'tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stripe_enabled' => ['nullable', 'in:0,1'],
            'paypal_enabled' => ['nullable', 'in:0,1'],
            'manual_enabled' => ['nullable', 'in:0,1'],
            'manual_instructions' => ['nullable', 'string', 'max:2000'],
            'auto_email_on_invoice_create' => ['nullable', 'in:0,1'],
            'reminder_enabled' => ['nullable', 'in:0,1'],
            'reminder_days_before' => ['nullable', 'integer', 'min:1', 'max:30'],
            'reminder_send_on_due_date' => ['nullable', 'in:0,1'],
            'overdue_reminder_enabled' => ['nullable', 'in:0,1'],
            'overdue_reminder_every_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $includeTax = (string) ($validated['include_tax_on_pdf'] ?? '0') === '1';
        $taxRate = round((float) ($validated['tax_rate_percent'] ?? 0), 2);

        $settings = $this->resolveInvoiceSettings();
        $settings->include_tax_on_pdf = $includeTax;
        $settings->tax_rate_percent = $taxRate;
        $settings->stripe_enabled = (bool) ($validated['stripe_enabled'] ?? false);
        $settings->paypal_enabled = (bool) ($validated['paypal_enabled'] ?? false);
        $settings->manual_enabled = (bool) ($validated['manual_enabled'] ?? false);
        $settings->manual_instructions = $validated['manual_instructions'] ?? null;
        if (Schema::hasColumn('invoice_settings', 'auto_email_on_invoice_create')) {
            $settings->auto_email_on_invoice_create = (bool) ($validated['auto_email_on_invoice_create'] ?? true);
        }
        if (Schema::hasColumn('invoice_settings', 'reminder_enabled')) {
            $settings->reminder_enabled = (bool) ($validated['reminder_enabled'] ?? true);
        }
        if (Schema::hasColumn('invoice_settings', 'reminder_days_before')) {
            $settings->reminder_days_before = (int) ($validated['reminder_days_before'] ?? 3);
        }
        if (Schema::hasColumn('invoice_settings', 'reminder_send_on_due_date')) {
            $settings->reminder_send_on_due_date = (bool) ($validated['reminder_send_on_due_date'] ?? true);
        }
        if (Schema::hasColumn('invoice_settings', 'overdue_reminder_enabled')) {
            $settings->overdue_reminder_enabled = (bool) ($validated['overdue_reminder_enabled'] ?? true);
        }
        if (Schema::hasColumn('invoice_settings', 'overdue_reminder_every_days')) {
            $settings->overdue_reminder_every_days = (int) ($validated['overdue_reminder_every_days'] ?? 3);
        }
        $settings->save();

        return back()->with('status', 'Invoice settings updated.');
    }

    public function adminEmailsIndex(Request $request): View
    {
        return $this->adminEmailsInbox($request);
    }

    public function adminEmailsInbox(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        return $this->renderAdminEmailMailbox($request, 'inbox');
    }

    public function adminEmailsSent(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        return $this->renderAdminEmailMailbox($request, 'sent');
    }

    public function adminEmailsDrafts(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        return $this->renderAdminEmailMailbox($request, 'drafts');
    }

    public function adminApiIntegrationsIndex(Request $request): View
    {
        $this->ensureOwnerAdminAccess($request);

        return view('admin.api-integrations', [
            'settings' => $this->buildApiDisplaySettings(),
            'rawSettings' => $this->resolveApiIntegrationSettings(),
        ]);
    }

    public function adminApiIntegrationsUpdate(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_sandbox' => ['nullable', 'in:0,1'],
            'mail_mailer' => ['nullable', 'string', 'max:50'],
            'mail_host' => ['nullable', 'string', 'max:120'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:120'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'max:20'],
            'mail_from_address' => ['nullable', 'email', 'max:120'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'inbound_mail_enabled' => ['nullable', 'in:0,1'],
            'inbound_mail_provider' => ['nullable', 'string', 'max:20'],
            'inbound_mail_host' => ['nullable', 'string', 'max:160'],
            'inbound_mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'inbound_mail_encryption' => ['nullable', 'string', 'max:20'],
            'inbound_mail_username' => ['nullable', 'string', 'max:160'],
            'inbound_mail_password' => ['nullable', 'string', 'max:255'],
            'inbound_mail_mailbox' => ['nullable', 'string', 'max:120'],
            'inbound_mail_search' => ['nullable', 'string', 'max:40'],
            'inbound_mail_max_per_run' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'inbound_mail_delete_after_process' => ['nullable', 'in:0,1'],
            'media_disk' => ['nullable', 'string', 'max:20'],
            's3_key' => ['nullable', 'string', 'max:255'],
            's3_secret' => ['nullable', 'string', 'max:255'],
            's3_region' => ['nullable', 'string', 'max:120'],
            's3_bucket' => ['nullable', 'string', 'max:120'],
            's3_endpoint' => ['nullable', 'string', 'max:255'],
            's3_path_style' => ['nullable', 'in:0,1'],
            'outbound_webhook_enabled' => ['nullable', 'in:0,1'],
            'outbound_webhook_url' => ['nullable', 'url', 'max:255'],
            'outbound_webhook_secret' => ['nullable', 'string', 'max:255'],
            'chat_provider' => ['nullable', 'string', 'max:80'],
            'chat_api_key' => ['nullable', 'string', 'max:255'],
            'chat_webhook_url' => ['nullable', 'url', 'max:255'],
            'ai_provider' => ['nullable', 'string', 'max:60'],
            'ai_model' => ['nullable', 'string', 'max:120'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_base_url' => ['nullable', 'string', 'max:255'],
            'openrouter_api_key' => ['nullable', 'string', 'max:255'],
            'openrouter_base_url' => ['nullable', 'string', 'max:255'],
            'openrouter_model' => ['nullable', 'string', 'max:120'],
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
            'gemini_base_url' => ['nullable', 'string', 'max:255'],
            'gemini_model' => ['nullable', 'string', 'max:120'],
        ]);

        $settings = $this->resolveApiIntegrationSettings();
        $settings->fill([
            'stripe_publishable_key' => $validated['stripe_publishable_key'] ?? null,
            'stripe_secret_key' => $validated['stripe_secret_key'] ?? null,
            'paypal_client_id' => $validated['paypal_client_id'] ?? null,
            'paypal_secret' => $validated['paypal_secret'] ?? null,
            'paypal_sandbox' => (bool) ($validated['paypal_sandbox'] ?? true),
            'mail_mailer' => $validated['mail_mailer'] ?? null,
            'mail_host' => $validated['mail_host'] ?? null,
            'mail_port' => $validated['mail_port'] ?? null,
            'mail_username' => $validated['mail_username'] ?? null,
            'mail_password' => $validated['mail_password'] ?? null,
            'mail_encryption' => $validated['mail_encryption'] ?? null,
            'mail_from_address' => $validated['mail_from_address'] ?? null,
            'mail_from_name' => $validated['mail_from_name'] ?? null,
            'inbound_mail_enabled' => isset($validated['inbound_mail_enabled']) ? (bool) $validated['inbound_mail_enabled'] : null,
            'inbound_mail_provider' => $validated['inbound_mail_provider'] ?? null,
            'inbound_mail_host' => $validated['inbound_mail_host'] ?? null,
            'inbound_mail_port' => $validated['inbound_mail_port'] ?? null,
            'inbound_mail_encryption' => $validated['inbound_mail_encryption'] ?? null,
            'inbound_mail_username' => $validated['inbound_mail_username'] ?? null,
            'inbound_mail_password' => $validated['inbound_mail_password'] ?? null,
            'inbound_mail_mailbox' => $validated['inbound_mail_mailbox'] ?? null,
            'inbound_mail_search' => $validated['inbound_mail_search'] ?? null,
            'inbound_mail_max_per_run' => $validated['inbound_mail_max_per_run'] ?? null,
            'inbound_mail_delete_after_process' => isset($validated['inbound_mail_delete_after_process']) ? (bool) $validated['inbound_mail_delete_after_process'] : null,
            'media_disk' => $validated['media_disk'] ?? null,
            's3_key' => $validated['s3_key'] ?? null,
            's3_secret' => $validated['s3_secret'] ?? null,
            's3_region' => $validated['s3_region'] ?? null,
            's3_bucket' => $validated['s3_bucket'] ?? null,
            's3_endpoint' => $validated['s3_endpoint'] ?? null,
            's3_path_style' => (bool) ($validated['s3_path_style'] ?? false),
            'outbound_webhook_enabled' => (bool) ($validated['outbound_webhook_enabled'] ?? false),
            'outbound_webhook_url' => $validated['outbound_webhook_url'] ?? null,
            'outbound_webhook_secret' => $validated['outbound_webhook_secret'] ?? null,
            'chat_provider' => $validated['chat_provider'] ?? null,
            'chat_api_key' => $validated['chat_api_key'] ?? null,
            'chat_webhook_url' => $validated['chat_webhook_url'] ?? null,
            'ai_provider' => $validated['ai_provider'] ?? null,
            'ai_model' => $validated['ai_model'] ?? null,
            'openai_api_key' => $validated['openai_api_key'] ?? null,
            'openai_base_url' => $validated['openai_base_url'] ?? null,
            'openrouter_api_key' => $validated['openrouter_api_key'] ?? null,
            'openrouter_base_url' => $validated['openrouter_base_url'] ?? null,
            'openrouter_model' => $validated['openrouter_model'] ?? null,
            'gemini_api_key' => $validated['gemini_api_key'] ?? null,
            'gemini_base_url' => $validated['gemini_base_url'] ?? null,
            'gemini_model' => $validated['gemini_model'] ?? null,
        ]);
        $settings->save();

        return back()->with('status', 'API integration settings saved.');
    }

    public function adminSettingsIndex(Request $request): View
    {
        $this->ensureOwnerAdminAccess($request);

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

        $invoiceSettings = $this->resolveInvoiceSettings();
        $canManageInvoiceSettings = in_array(strtolower(trim((string) $request->user()?->role)), ['owner', 'admin'], true);

        $currencySettings = $this->resolveCurrencySettings();
        $currencyOptions = $this->currencyOptions();

        return view('admin.settings-index', [
            'apiSettings' => $this->buildApiDisplaySettings(),
            'watermarkSettings' => $settings,
            'unpaidImageTotal' => $unpaidImageTotal,
            'upToDateWatermarks' => $upToDateWatermarks,
            'pendingRebuild' => $pendingRebuild,
            'logoExists' => $logoExists,
            'invoiceSettings' => $invoiceSettings,
            'canManageInvoiceSettings' => $canManageInvoiceSettings,
            'currencySettings' => $currencySettings,
            'currencyOptions' => $currencyOptions,
        ]);
    }

    public function adminCurrencySettingsUpdate(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        if (!Schema::hasTable('currency_settings')) {
            return back()->withErrors(['currency' => 'Currency settings table not found. Run migrations.']);
        }

        $options = $this->currencyOptions();
        $allowed = array_keys($options);

        $validated = $request->validate([
            'default_currency' => ['required', Rule::in($allowed)],
            'enabled_currencies' => ['nullable', 'array'],
            'enabled_currencies.*' => [Rule::in($allowed)],
        ]);

        $enabled = array_values(array_unique($validated['enabled_currencies'] ?? []));
        if ($enabled === []) {
            $enabled = [$validated['default_currency']];
        }

        if (!in_array($validated['default_currency'], $enabled, true)) {
            $enabled[] = $validated['default_currency'];
        }

        $settings = $this->resolveCurrencySettings();
        $settings->default_currency = $validated['default_currency'];
        $settings->enabled_currencies = $enabled;
        $settings->save();

        return back()->with('status', 'Currency settings updated.');
    }
    public function adminEmailAutomationSettingsIndex(Request $request): View
    {
        $this->ensureOwnerAdminAccess($request);

        $sourceMap = $this->leadAutoEmailSources();
        $captureService = app(LeadAutoCaptureService::class);
        $records = LeadAutoEmailSetting::query()
            ->whereIn('source', array_keys($sourceMap))
            ->get()
            ->keyBy('source');

        $sourceSettings = collect($sourceMap)->map(function (array $meta, string $source) use ($records, $captureService): array {
            $record = $records->get($source);
            $defaults = $captureService->resolveSourceSettings($source);

            return [
                'source' => $source,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'enabled' => $record?->exists ? (bool) $record->enabled : (bool) ($defaults['enabled'] ?? true),
                'tone' => $record?->exists ? (string) ($record->tone ?? 'professional') : (string) ($defaults['tone'] ?? 'professional'),
                'template_prompt' => $record?->exists ? (string) ($record->template_prompt ?? '') : (string) ($defaults['template_prompt'] ?? ''),
                'subject_prefix' => $record?->exists ? (string) ($record->subject_prefix ?? '') : (string) ($defaults['subject_prefix'] ?? ''),
            ];
        })->values();

        return view('admin.emails-automation', [
            'sourceSettings' => $sourceSettings,
            'recentAutomationEvents' => LeadEvent::query()
                ->with('leadProfile:id,email,name')
                ->whereIn('event_type', ['welcome_email_sent', 'welcome_email_failed'])
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function adminEmailAutomationSettingsUpdate(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $sourceMap = $this->leadAutoEmailSources();
        $allowedSources = array_keys($sourceMap);

        $validated = $request->validate([
            'enabled' => ['nullable', 'array'],
            'enabled.*' => ['nullable', 'in:0,1'],
            'tone' => ['nullable', 'array'],
            'tone.*' => ['nullable', 'string', 'max:40'],
            'template_prompt' => ['nullable', 'array'],
            'template_prompt.*' => ['nullable', 'string', 'max:5000'],
            'subject_prefix' => ['nullable', 'array'],
            'subject_prefix.*' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ($allowedSources as $source) {
            $enabled = (string) data_get($validated, "enabled.{$source}", '0') === '1';
            $tone = trim((string) data_get($validated, "tone.{$source}", 'professional'));
            $templatePrompt = trim((string) data_get($validated, "template_prompt.{$source}", ''));
            $subjectPrefix = trim((string) data_get($validated, "subject_prefix.{$source}", ''));

            LeadAutoEmailSetting::query()->updateOrCreate(
                ['source' => $source],
                [
                    'enabled' => $enabled,
                    'tone' => $tone !== '' ? Str::limit($tone, 40, '') : 'professional',
                    'template_prompt' => $templatePrompt !== '' ? Str::limit($templatePrompt, 5000, '') : null,
                    'subject_prefix' => $subjectPrefix !== '' ? Str::limit($subjectPrefix, 120, '') : null,
                ]
            );
        }

        return back()->with('status', 'Lead automation email settings updated.');
    }

    public function adminEmailAutomationBackfillRun(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'mode' => ['required', 'in:dry-run,live'],
        ]);

        $isDryRun = (string) ($validated['mode'] ?? 'dry-run') === 'dry-run';

        try {
            $exitCode = $isDryRun
                ? Artisan::call('leads:backfill-welcome-emails', ['--dry-run' => true])
                : Artisan::call('leads:backfill-welcome-emails');

            $output = trim((string) Artisan::output());
            $modeLabel = $isDryRun ? 'dry run' : 'live run';

            if ($exitCode !== 0) {
                return back()
                    ->withErrors(['automation_backfill' => 'Backfill ' . $modeLabel . ' failed.'])
                    ->with('automation_backfill_output', $output)
                    ->with('automation_backfill_mode', $validated['mode']);
            }

            return back()
                ->with('status', 'Lead welcome backfill ' . $modeLabel . ' completed successfully.')
                ->with('automation_backfill_output', $output)
                ->with('automation_backfill_mode', $validated['mode']);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['automation_backfill' => 'Backfill command crashed: ' . Str::limit($exception->getMessage(), 240)])
                ->with('automation_backfill_mode', $validated['mode']);
        }
    }

    public function adminEmailAiWrite(Request $request): JsonResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'goal' => ['nullable', 'string', 'max:500'],
            'tone' => ['nullable', 'string', 'max:80'],
            'template' => ['nullable', 'string', 'in:custom,cold_followup,quote_reminder,no_response_nudge'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'context' => ['nullable', 'string', 'max:5000'],
        ]);

        $tone = trim((string) ($validated['tone'] ?? 'professional'));
        $template = trim((string) ($validated['template'] ?? 'custom'));
        $recipientName = trim((string) ($validated['recipient_name'] ?? ''));
        $context = trim((string) ($validated['context'] ?? ''));
        $goal = trim((string) ($validated['goal'] ?? ''));

        $templateInstructions = [
            'custom' => 'Use a general business outreach structure.',
            'cold_followup' => 'Lead-specific cold follow-up: warm re-introduction, remind value, ask for a short call this week.',
            'quote_reminder' => 'Lead-specific quote reminder: reference an existing quote/proposal and ask whether they have questions before decision.',
            'no_response_nudge' => 'Lead-specific no-response nudge: polite check-in, reduce friction, offer two simple next-step choices.',
        ];
        $templateDefaultGoals = [
            'custom' => 'Follow up with this lead and propose a next step.',
            'cold_followup' => 'Send a short warm follow-up to restart conversation and ask for a 10-minute call.',
            'quote_reminder' => 'Remind the lead about their quote and ask if they want any adjustments before confirming.',
            'no_response_nudge' => 'Send a polite no-response nudge with two low-friction options to continue.',
        ];

        $resolvedTemplate = array_key_exists($template, $templateInstructions) ? $template : 'custom';
        if ($goal === '') {
            $goal = (string) ($templateDefaultGoals[$resolvedTemplate] ?? $templateDefaultGoals['custom']);
        }

        $prompt = implode("\n", [
            'Write a concise business email for CRM outreach.',
            'Return plain text only using this strict format:',
            'Subject: <subject line>',
            'Body:',
            '<email body>',
            '',
            'Constraints:',
            '- Keep the tone professional and clear.',
            '- Include one clear call to action.',
            '- Avoid placeholders and markdown formatting.',
            '',
            'Template: ' . $resolvedTemplate,
            'Template instruction: ' . $templateInstructions[$resolvedTemplate],
            'Tone: ' . ($tone !== '' ? $tone : 'professional'),
            'Recipient name: ' . ($recipientName !== '' ? $recipientName : 'Not provided'),
            'Goal: ' . $goal,
            'Context: ' . ($context !== '' ? $context : 'No additional context'),
        ]);

        try {
            $provider = app(AiProviderManager::class)->provider();
            $usage = $provider->chat([
                ['role' => 'system', 'content' => 'You are an expert CRM email copywriter.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $content = trim((string) ($usage['content'] ?? ''));
            $subject = '';
            $body = $content;

            if (preg_match('/^Subject:\s*(.+)$/mi', $content, $subjectMatch) === 1) {
                $subject = trim((string) ($subjectMatch[1] ?? ''));
            }
            if (preg_match('/^Body:\s*(.*)$/mis', $content, $bodyMatch) === 1) {
                $body = trim((string) ($bodyMatch[1] ?? ''));
            }

            if ($subject === '') {
                $subject = Str::limit($goal !== '' ? $goal : 'CRM follow up', 120, '');
            }

            return response()->json([
                'ok' => true,
                'subject' => $subject,
                'message' => $body,
                'provider' => $provider->name(),
                'model' => (string) ($usage['model'] ?? ''),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $fallbackSubjectMap = [
                'cold_followup' => 'Quick follow-up on your request',
                'quote_reminder' => 'Friendly reminder about your quote',
                'no_response_nudge' => 'Checking in on your next step',
            ];

            $fallbackSubject = Str::limit($goal !== '' ? $goal : (string) ($fallbackSubjectMap[$resolvedTemplate] ?? 'CRM follow up'), 120, '');
            $fallbackBody = implode("\n", array_filter([
                $recipientName !== '' ? "Hi {$recipientName}," : 'Hi there,',
                '',
                $goal,
                $resolvedTemplate === 'cold_followup' ? 'I wanted to follow up and see if you are available this week for a quick call.' : null,
                $resolvedTemplate === 'quote_reminder' ? 'Just checking whether you had a chance to review the quote and if any details need clarification.' : null,
                $resolvedTemplate === 'no_response_nudge' ? 'I know schedules get busy, so I wanted to send a quick nudge and make next steps easy.' : null,
                $context !== '' ? '' : null,
                $context !== '' ? $context : null,
                '',
                'Best regards,',
                'Alessio Battista',
                'Maccento Real Estate Media',
            ], static fn ($line): bool => $line !== null));

            return response()->json([
                'ok' => true,
                'subject' => $fallbackSubject,
                'message' => $fallbackBody,
                'provider' => 'fallback',
                'model' => 'n/a',
            ]);
        }
    }

    public function adminEmailDraftStore(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'draft_id' => ['nullable', 'integer'],
            'recipient_email' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'string', 'max:255'],
            'cc' => ['nullable', 'string', 'max:500'],
            'bcc' => ['nullable', 'string', 'max:500'],
            'client_project_id' => ['nullable', 'integer'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['nullable', 'string', 'max:10000'],
        ]);

        $draft = null;
        if (!blank($validated['draft_id'] ?? null)) {
            $draft = EmailDraft::query()->find((int) $validated['draft_id']);
        }

        if ($draft === null) {
            $draft = new EmailDraft();
            $draft->created_by = $request->user()?->id;
        }

        $forcedReplyTo = $this->crmInboundReplyToAddress();

        $draft->recipient_email = filled($validated['recipient_email'] ?? null) ? (string) $validated['recipient_email'] : null;
        $draft->reply_to = $forcedReplyTo;
        $draft->cc = filled($validated['cc'] ?? null) ? (string) $validated['cc'] : null;
        $draft->bcc = filled($validated['bcc'] ?? null) ? (string) $validated['bcc'] : null;
        $draft->client_project_id = !blank($validated['client_project_id'] ?? null) ? (int) $validated['client_project_id'] : null;
        $draft->subject = filled($validated['subject'] ?? null) ? (string) $validated['subject'] : null;
        $draft->message = filled($validated['message'] ?? null) ? (string) $validated['message'] : null;
        $draft->status = 'draft';
        $draft->last_opened_at = now();
        $draft->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'draft_id' => (int) $draft->id,
                'updated_at' => optional($draft->updated_at)->toIso8601String(),
            ]);
        }

        return redirect()->route('admin.emails.drafts', ['draft' => $draft->id])->with('status', 'Draft saved.');
    }

    public function adminEmailDraftDelete(Request $request, EmailDraft $draft): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $draft->delete();

        return redirect()->route('admin.emails.drafts')->with('status', 'Draft deleted.');
    }

    public function adminEmailInboxDelete(Request $request, InboundEmail $inbound): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $inbound->delete();

        return redirect()->route('admin.emails.inbox')->with('status', 'Inbox email deleted.');
    }

    public function adminEmailSentDelete(Request $request, EmailLog $emailLog): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $emailLog->delete();

        return redirect()->route('admin.emails.sent')->with('status', 'Sent email deleted.');
    }

    public function adminEmailDraftSend(Request $request, EmailDraft $draft): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        if (blank($draft->recipient_email) || blank($draft->subject) || blank($draft->message)) {
            return back()->withErrors(['recipient_email' => 'Draft must include recipient, subject, and message before sending.']);
        }

        $ccList = $this->parseEmailList((string) ($draft->cc ?? ''));
        $bccList = $this->parseEmailList((string) ($draft->bcc ?? ''));
        if (count($ccList['invalid']) > 0 || count($bccList['invalid']) > 0) {
            return back()->withErrors(['recipient_email' => 'Draft has invalid CC/BCC addresses. Please edit and try again.']);
        }

        $subject = trim((string) $draft->subject);
        $threadProjectId = $this->resolveOutboundThreadProjectId((string) $draft->recipient_email, $draft->client_project_id ? (int) $draft->client_project_id : null);
        if ($threadProjectId !== null) {
            $subject = $this->appendProjectThreadTag($subject, $threadProjectId);
        }

        $result = $this->dispatchCrmEmail([
            'created_by' => $request->user()?->id,
            'mode' => 'draft',
            'template_key' => null,
            'recipient_email' => (string) $draft->recipient_email,
            'reply_to' => $this->crmInboundReplyToAddress(),
            'cc' => $ccList['valid'],
            'bcc' => $bccList['valid'],
            'subject' => $subject,
            'message' => (string) $draft->message,
            'thread_project_id' => $threadProjectId,
        ]);

        if (!$result['ok']) {
            return back()->withErrors(['recipient_email' => (string) ($result['error'] ?? 'Email could not be sent from draft.')]);
        }

        $draft->status = 'sent';
        $draft->last_opened_at = now();
        $draft->save();

        return redirect()->route('admin.emails.sent')->with('status', 'Draft sent successfully.');
    }

    public function adminLeadEmailSend(Request $request, LeadProfile $lead): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        if (blank($lead->email)) {
            return back()->withErrors(['lead' => 'This lead does not have an email address.']);
        }

        $subject = 'Follow-up from Maccento CRM';
        if (!blank($lead->service_type)) {
            $subject = 'Follow-up for your ' . (string) $lead->service_type . ' request';
        }

        $message = implode("\n", [
            'Hi ' . ($lead->name ?: 'there') . ',',
            '',
            'Thank you for your interest in Maccento. We are following up to help you move forward with your request.',
            'Please reply to this email with your availability and preferred next step.',
            '',
            'Best regards,',
            'Alessio Battista',
            'Maccento Real Estate Media',
        ]);

        $result = $this->dispatchCrmEmail([
            'created_by' => $request->user()?->id,
            'mode' => 'lead_followup',
            'template_key' => 'lead_followup',
            'recipient_email' => (string) $lead->email,
            'reply_to' => null,
            'cc' => [],
            'bcc' => [],
            'subject' => $subject,
            'message' => $message,
            'thread_project_id' => null,
        ]);

        if (!$result['ok']) {
            return back()->withErrors(['lead' => (string) ($result['error'] ?? 'Lead email could not be sent.')]);
        }

        return back()->with('status', 'Lead email sent to ' . (string) $lead->email . '.');
    }

    public function adminEmailSend(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $forcedReplyTo = $this->crmInboundReplyToAddress();

        $validated = $request->validate([
            'draft_id' => ['nullable', 'integer'],
            'mode' => ['required', 'in:template,custom'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'client_project_id' => ['nullable', 'integer'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'cc' => ['nullable', 'string', 'max:500'],
            'bcc' => ['nullable', 'string', 'max:500'],
            'template_key' => ['nullable', 'string', 'in:delivery_test,pipeline_snapshot,followup_digest'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $recipient = trim((string) ($validated['recipient_email'] ?? ''));
        $draft = null;
        if (!blank($validated['draft_id'] ?? null)) {
            $draft = EmailDraft::query()->find((int) $validated['draft_id']);
        }
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

        $attachments = $this->normalizeOutboundAttachments($request->file('attachments', []));

        $emailLog = $this->createEmailLogEntry([
            'created_by' => $request->user()?->id,
            'mode' => (string) ($validated['mode'] ?? 'custom'),
            'template_key' => (string) ($validated['template_key'] ?? ''),
            'recipient_email' => $recipient,
            'reply_to' => $forcedReplyTo,
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
                ctaUrl: route('admin.emails.sent'),
                footerNote: 'Need help? Reply to this email and our team will assist you.',
                emailLogId: $emailLog?->id,
                threadProjectId: $threadProjectId,
                replyToAddress: $forcedReplyTo,
                outboundAttachmentMeta: $attachments,
            ));

            $this->notificationService()->notifyInternal(
                'admin_email_sent',
                'CRM email sent',
                "Email sent to {$recipient}: {$subject}",
                route('admin.emails.sent'),
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
                    'reply_to' => $forcedReplyTo,
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

            if ($draft !== null) {
                $draft->status = 'sent';
                $draft->last_opened_at = now();
                $draft->save();
            }

            if ($emailLog !== null) {
                $this->logActivity(
                    $request,
                    'email',
                    $emailLog->id,
                    null,
                    $request->user(),
                    'send',
                    'Email sent to ' . $recipient,
                    [
                        'subject' => $subject,
                        'template_key' => (string) ($validated['template_key'] ?? ''),
                    ]
                );
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
                    'reply_to' => $forcedReplyTo,
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
            return back()->withErrors(['recipient_email' => 'Email could not be sent. Please verify SMTP settings and try again.'])->withInput();
        }

        return redirect()->route('admin.emails.sent')->with('status', 'Email sent successfully.');
    }

    public function adminQuoteManualStore(Request $request): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $allowedCurrencies = $this->resolveAllowedCurrencyCodes();
        $currencyRule = ['nullable', 'string', 'max:8'];
        if ($allowedCurrencies !== []) {
            $currencyRule[] = Rule::in($allowedCurrencies);
        }

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'services' => ['required', 'string', 'max:255'],
            'listing_type' => ['nullable', 'in:home,condo,rental,chalet,other'],
            'estimated_total' => ['required', 'integer', 'min:0'],
            'currency' => $currencyRule,
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
    public function adminBookingRequestDestroy(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [
            'action' => 'delete',
            'snapshot' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes', 'status']),
        ]);

        $bookingRequest->delete();

        return back()->with('status', 'Booking request deleted.');
    }

    public function adminServiceRequestDestroy(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [
            'action' => 'delete',
            'snapshot' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date', 'status']),
        ]);

        $serviceRequest->delete();

        return back()->with('status', 'Service request deleted.');
    }
    public function adminBookingRequestUpdate(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'requested_service' => ['required', 'string', 'max:160'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time_window' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes']);

        $bookingRequest->client_project_id = $validated['client_project_id'] ?? null;
        $bookingRequest->requested_service = $validated['requested_service'];
        $bookingRequest->preferred_date = $validated['preferred_date'] ?? null;
        $bookingRequest->preferred_time_window = $validated['preferred_time_window'] ?? null;
        $bookingRequest->notes = $validated['notes'] ?? null;
        $bookingRequest->save();

        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [
            'action' => 'update',
            'before' => $before,
            'after' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes']),
        ]);

        return back()->with('status', 'Booking request updated.');
    }

    public function adminServiceRequestUpdate(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'requested_service' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        $before = $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']);

        $serviceRequest->client_project_id = $validated['client_project_id'] ?? null;
        $serviceRequest->requested_service = $validated['requested_service'];
        $serviceRequest->subject = $validated['subject'] ?? null;
        $serviceRequest->details = $validated['details'] ?? null;
        $serviceRequest->preferred_date = $validated['preferred_date'] ?? null;
        $serviceRequest->save();

        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [
            'action' => 'update',
            'before' => $before,
            'after' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']),
        ]);

        return back()->with('status', 'Service request updated.');
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

    public function adminFormSubmissionDestroy(Request $request, WebsiteFormSubmission $submission): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $submission->delete();

        return back()->with('status', 'Submission deleted.');
    }

    public function adminBookingRequestsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $statusFilter = trim((string) $request->string('status'));
        $search = trim((string) $request->string('search'));

        $query = BookingRequest::query()
            ->with(['client:id,name,email,phone', 'project:id,title,scheduled_at'])
            ->latest('created_at');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('requested_service', 'like', '%' . $search . '%')
                    ->orWhereHas('client', function ($clientQuery) use ($search): void {
                        $clientQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('project', function ($projectQuery) use ($search): void {
                        $projectQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        $bookingRequests = $query->paginate(20)->appends($request->query());

        $editRequest = null;
        if ($request->filled('edit')) {
            $editRequest = BookingRequest::query()
                ->with(['client:id,name,email,phone', 'project:id,title,scheduled_at'])
                ->find((int) $request->input('edit'));
        }

        $statusOptions = ['new', 'proposed', 'confirmed', 'rescheduled', 'cancelled', 'closed'];

        return view('admin.booking-requests-index', [
            'bookingRequests' => $bookingRequests,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'statusOptions' => $statusOptions,
            'editRequest' => $editRequest,
        ]);
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
            ->paginate(12)
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

    public function adminServiceRequestsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $statusFilter = trim((string) $request->string('status'));
        $search = trim((string) $request->string('search'));

        $query = ClientServiceRequest::query()
            ->with(['client:id,name,email,phone', 'project:id,title'])
            ->latest('id');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('requested_service', 'like', '%' . $search . '%')
                    ->orWhereHas('client', function ($clientQuery) use ($search): void {
                        $clientQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('project', function ($projectQuery) use ($search): void {
                        $projectQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        $serviceRequests = $query->paginate(20)->appends($request->query());
        $statusOptions = ['new', 'accepted', 'in_progress', 'completed', 'closed'];
        $canManagePipeline = true;
        $currencyOptions = $this->currencyOptions();
        $defaultCurrency = $this->resolveDefaultCurrency();

        $editRequest = null;
        if ($request->filled('edit')) {
            $editRequest = ClientServiceRequest::query()
                ->with(['client:id,name,email,phone', 'project:id,title'])
                ->find((int) $request->input('edit'));
        }

        return view('admin.service-requests-index', [
            'serviceRequests' => $serviceRequests,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'statusOptions' => $statusOptions,
            'canManagePipeline' => $canManagePipeline,
            'editRequest' => $editRequest,
            'currencyOptions' => $currencyOptions,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    public function adminRequestEditLogsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $typeFilter = trim((string) $request->string('type'));
        $actionFilter = trim((string) $request->string('action'));
        $search = trim((string) $request->string('search'));

        $query = RequestEditLog::query()
            ->with([
                'actor:id,name,email,role',
                'client:id,name,email,phone',
            ])
            ->latest('id');

        if ($typeFilter !== '') {
            $query->where(function ($inner) use ($typeFilter): void {
                $inner->where('entity_type', $typeFilter)
                    ->orWhere('request_type', $typeFilter);
            });
        }

        if ($actionFilter !== '') {
            $query->where(function ($inner) use ($actionFilter): void {
                $inner->where('action', $actionFilter)
                    ->orWhere('changes->action', $actionFilter);
            });
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('request_type', 'like', '%' . $search . '%')
                    ->orWhere('entity_type', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%')
                    ->orWhere('changes->action', 'like', '%' . $search . '%');

                if (is_numeric($search)) {
                    $inner->orWhere('request_id', (int) $search)
                        ->orWhere('entity_id', (int) $search);
                }

                $inner->orWhereHas('client', function ($clientQuery) use ($search): void {
                    $clientQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })
                ->orWhereHas('actor', function ($actorQuery) use ($search): void {
                    $actorQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            });
        }

        $logs = $query->paginate(25)->appends($request->query());
        $typeOptions = [
            'booking',
            'service',
            'lead',
            'quote',
            'invoice',
            'project',
            'client',
            'user',
            'message',
            'media',
            'email',
        ];
        $actionOptions = ['create', 'update', 'status_update', 'delete', 'send', 'upload', 'download', 'payment'];

        return view('admin.request-edit-logs-index', [
            'logs' => $logs,
            'typeFilter' => $typeFilter,
            'actionFilter' => $actionFilter,
            'search' => $search,
            'typeOptions' => $typeOptions,
            'actionOptions' => $actionOptions,
        ]);
    }

    public function adminReportsIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        [$fromDateRaw, $toDateRaw] = $this->extractDateRange($request, 'from', 'to');
        $fromDateRaw ??= now()->subDays(30)->toDateString();
        $toDateRaw ??= now()->toDateString();

        $fromDate = Carbon::parse($fromDateRaw)->startOfDay();
        $toDate = Carbon::parse($toDateRaw)->endOfDay();

        $issuedInvoicesQuery = ClientInvoice::query()
            ->whereDate('issued_at', '>=', $fromDate->toDateString())
            ->whereDate('issued_at', '<=', $toDate->toDateString());

        $paidInvoicesQuery = ClientInvoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$fromDate, $toDate]);

        $issuedCount = (clone $issuedInvoicesQuery)->count();
        $paidCount = (clone $paidInvoicesQuery)->count();
        $unpaidCount = (clone $issuedInvoicesQuery)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->count();

        $revenueByCurrency = (clone $paidInvoicesQuery)
            ->select('currency', DB::raw('sum(amount) as total'))
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $outstandingByCurrency = ClientInvoice::query()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereDate('issued_at', '>=', $fromDate->toDateString())
            ->whereDate('issued_at', '<=', $toDate->toDateString())
            ->select('currency', DB::raw('sum(amount) as total'))
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $leadsCreated = LeadProfile::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
        $leadsWon = LeadProfile::query()
            ->where('status', 'won')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->count();

        $quotesCreated = QuoteBuild::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
        $quotesBooked = QuoteBuild::query()
            ->where('status', 'booked')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->count();

        $projectsCompleted = ClientProject::query()
            ->where('status', 'complete')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->count();
        $projectsOverdue = ClientProject::query()
            ->whereIn('status', ['accepted', 'shooting', 'editing'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $paidInvoices = (clone $paidInvoicesQuery)->get(['issued_at', 'paid_at']);
        $avgDaysToPay = null;
        if ($paidInvoices->isNotEmpty()) {
            $sumDays = 0;
            $countDays = 0;
            foreach ($paidInvoices as $invoice) {
                if ($invoice->issued_at && $invoice->paid_at) {
                    $sumDays += Carbon::parse($invoice->issued_at)->diffInDays(Carbon::parse($invoice->paid_at));
                    $countDays++;
                }
            }
            if ($countDays > 0) {
                $avgDaysToPay = round($sumDays / $countDays, 1);
            }
        }

        $overdueAging = [
            '0_7' => 0,
            '8_14' => 0,
            '15_30' => 0,
            '31_plus' => 0,
        ];
        $overdueInvoices = ClientInvoice::query()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get(['due_date']);
        foreach ($overdueInvoices as $invoice) {
            $days = Carbon::parse($invoice->due_date)->diffInDays(now());
            if ($days <= 7) {
                $overdueAging['0_7']++;
            } elseif ($days <= 14) {
                $overdueAging['8_14']++;
            } elseif ($days <= 30) {
                $overdueAging['15_30']++;
            } else {
                $overdueAging['31_plus']++;
            }
        }

        $topClients = DB::table('client_invoices')
            ->join('clients', 'clients.id', '=', 'client_invoices.client_id')
            ->select(
                'clients.id',
                'clients.name',
                'clients.email',
                'client_invoices.currency',
                DB::raw('sum(client_invoices.amount) as total'),
                DB::raw('count(*) as invoices_count')
            )
            ->where('client_invoices.status', 'paid')
            ->whereBetween('client_invoices.paid_at', [$fromDate, $toDate])
            ->groupBy('clients.id', 'clients.name', 'clients.email', 'client_invoices.currency')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return view('admin.reports-index', [
            'fromDate' => $fromDate->toDateString(),
            'toDate' => $toDate->toDateString(),
            'issuedCount' => $issuedCount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'revenueByCurrency' => $revenueByCurrency,
            'outstandingByCurrency' => $outstandingByCurrency,
            'leadsCreated' => $leadsCreated,
            'leadsWon' => $leadsWon,
            'quotesCreated' => $quotesCreated,
            'quotesBooked' => $quotesBooked,
            'projectsCompleted' => $projectsCompleted,
            'projectsOverdue' => $projectsOverdue,
            'avgDaysToPay' => $avgDaysToPay,
            'overdueAging' => $overdueAging,
            'topClients' => $topClients,
        ]);
    }

    public function adminSystemHealthIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $now = now();
        $windowStart = $now->copy()->subHours(24);

        $failedJobs = [];
        $failedJobsCount = null;
        $latestFailedJobAt = null;
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get();
            $failedJobsCount = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', $windowStart)
                ->count();
            $latestFailedJobAt = DB::table('failed_jobs')->max('failed_at');
        }

        $failedEmails = [];
        $failedEmailsCount = null;
        if (Schema::hasTable('email_logs')) {
            $failedEmails = EmailLog::query()
                ->where('status', 'failed')
                ->latest('id')
                ->limit(20)
                ->get();
            $failedEmailsCount = (int) EmailLog::query()
                ->where('status', 'failed')
                ->where('created_at', '>=', $windowStart)
                ->count();
        }

        return view('admin.system-health-index', [
            'failedJobs' => $failedJobs,
            'failedJobsCount' => $failedJobsCount,
            'latestFailedJobAt' => $latestFailedJobAt ? Carbon::parse((string) $latestFailedJobAt) : null,
            'failedEmails' => $failedEmails,
            'failedEmailsCount' => $failedEmailsCount,
        ]);
    }

    public function adminBackupRestoreIndex(Request $request): View
    {
        $this->ensurePipelineWriteAccess($request);

        $backupFiles = Storage::disk('local')->exists('backups')
            ? Storage::disk('local')->files('backups')
            : [];

        $backupSettings = Schema::hasTable('backup_settings')
            ? BackupSetting::query()->first()
            : null;

        return view('admin.backup-restore', [
            'backupFiles' => $backupFiles,
            'backupSettings' => $backupSettings,
        ]);
    }

    public function adminBackupSettingsUpdate(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'enabled' => ['nullable', 'in:0,1'],
            'run_time' => ['required', 'date_format:H:i'],
            'run_days' => ['nullable', 'array'],
            'run_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'keep_count' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        if (!Schema::hasTable('backup_settings')) {
            return back()->withErrors(['backup' => 'Backup settings table not found. Run migrations.']);
        }

        $settings = BackupSetting::query()->first() ?? new BackupSetting();
        $settings->enabled = (bool) ($validated['enabled'] ?? false);
        $settings->run_time = (string) $validated['run_time'];
        $settings->run_days = $validated['run_days'] ?? [];
        $settings->keep_count = (int) $validated['keep_count'];
        $settings->save();

        return back()->with('status', 'Backup settings updated.');
    }

    public function adminBackupRunNow(Request $request)
    {
        $this->ensureOwnerAdminAccess($request);

        $exitCode = Artisan::call('system:db-backup', [
            '--force' => true,
            '--prune' => true,
        ]);
        $commandOutput = trim((string) Artisan::output());

        if ($exitCode !== 0) {
            $message = 'Backup failed. ';
            if ($commandOutput !== '') {
                $message .= 'Details: ' . Str::limit($commandOutput, 240);
            } else {
                $message .= 'Check server logs for details.';
            }
            return back()->withErrors(['backup' => $message]);
        }

        $backupFiles = Storage::disk('local')->exists('backups')
            ? Storage::disk('local')->files('backups')
            : [];

        if ($backupFiles === []) {
            return back()->withErrors(['backup' => 'Backup completed but no files were found.']);
        }

        $latestFile = null;
        $latestTimestamp = null;
        foreach ($backupFiles as $file) {
            $timestamp = Storage::disk('local')->lastModified($file);
            if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                $latestTimestamp = $timestamp;
                $latestFile = $file;
            }
        }

        if ($latestFile === null) {
            return back()->withErrors(['backup' => 'Backup completed but latest file could not be resolved.']);
        }

        return Storage::disk('local')->download($latestFile, basename($latestFile));
    }

    public function adminBackupRestore(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'backup_file' => ['required', 'string'],
        ]);

        $file = basename((string) $validated['backup_file']);
        if (!Storage::disk('local')->exists('backups/' . $file)) {
            return back()->withErrors(['backup_file' => 'Backup file not found.']);
        }

        Artisan::call('system:db-restore', [
            'file' => $file,
        ]);

        return back()->with('status', 'Restore command executed. Check logs for details.');
    }

    public function adminBackupUploadRestore(Request $request): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $validated = $request->validate([
            'backup_upload' => ['required', 'file', 'max:512000'],
        ]);

        $file = $validated['backup_upload'];
        $originalName = basename((string) ($file->getClientOriginalName() ?: 'backup.sql'));
        if (!preg_match('/\.(sql|sqlite)$/i', $originalName)) {
            return back()->withErrors(['backup_upload' => 'Unsupported backup file type.']);
        }

        $storedPath = $file->storeAs('backups', $originalName, 'local');
        if (!$storedPath) {
            return back()->withErrors(['backup_upload' => 'Failed to store backup file.']);
        }

        Artisan::call('system:db-restore', [
            'file' => $originalName,
        ]);

        return back()->with('status', 'Backup uploaded and restore command executed. Check logs for details.');
    }

    public function adminBackupDownload(Request $request)
    {
        $this->ensureOwnerAdminAccess($request);

        $file = basename((string) $request->query('file', ''));
        if ($file === '') {
            return back()->withErrors(['backup_file' => 'Backup file is required.']);
        }

        if (!preg_match('/\.(sql|sqlite)$/i', $file)) {
            return back()->withErrors(['backup_file' => 'Unsupported backup file type.']);
        }

        $path = 'backups/' . $file;
        if (!Storage::disk('local')->exists($path)) {
            return back()->withErrors(['backup_file' => 'Backup file not found.']);
        }

        return Storage::disk('local')->download($path, $file);
    }

    public function adminClientStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:client'],
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
            if ($this->tableHasColumn('users', 'phone') && blank($linkedUser->phone) && !blank($validated['phone'] ?? null)) {
                $linkedUser->phone = (string) $validated['phone'];
            }
            $linkedUser->password = $passwordForAdmin;
            if ($this->tableHasColumn('users', 'role') && !in_array((string) $linkedUser->role, ['client'], true)) {
                $linkedUser->role = $validated['role'];
            }
            $linkedUser->save();
            $accountMessage = 'Existing login account linked.';
        } else {
            $linkedUser = User::create($this->filterTableColumns('users', [
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'password' => $passwordForAdmin,
            ]));
            $accountMessage = 'Login created.';
        }

        if ($this->tableHasColumn('users', 'role') && !in_array((string) $linkedUser->role, ['client'], true)) {
            $linkedUser->role = $validated['role'];
            $linkedUser->save();
        }

        $client = Client::query()
            ->where('user_id', $linkedUser->id)
            ->orWhere('email', $email)
            ->first();

        $clientCreated = false;
        if (!$client) {
            $client = new Client();
            $clientCreated = true;
        }

        $clientBefore = $client->exists ? $client->only(['name', 'email', 'phone', 'company', 'status']) : null;

        $client->fill($this->filterTableColumns('clients', [
            'user_id' => $linkedUser->id,
            'created_by' => $client->created_by ?: $request->user()?->id,
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]));
        $client->save();

        $statusMessage = $clientCreated ? 'Client created.' : 'Existing client updated.';

        $this->logActivity(
            $request,
            'client',
            $client->id,
            $client->id,
            $request->user(),
            $clientCreated ? 'create' : 'update',
            $clientCreated ? 'Client created: ' . ($client->name ?: ('Client #' . $client->id)) : 'Client updated: ' . ($client->name ?: ('Client #' . $client->id)),
            [
                'before' => $clientBefore,
                'after' => $client->only(['name', 'email', 'phone', 'company', 'status']),
            ]
        );

        return redirect()->route('admin.clients.show', $client)->with('status', "{$statusMessage} {$accountMessage} Login password has been set.");
    }

    private function filterTableColumns(string $table, array $attributes): array
    {
        $allowed = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($attributes, $allowed);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
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
            'roles' => ['admin', 'manager', 'photographer', 'editor', 'client'],
        ]);
    }

    public function adminUserStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,manager,photographer,editor,client'],
            'password' => [
                'nullable',
                'string',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
            ],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $passwordForAdmin = trim((string) ($validated['password'] ?? ''));
        if ($passwordForAdmin === '') {
            $passwordForAdmin = 'Maccento@' . random_int(100000, 999999);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => $passwordForAdmin,
        ]);

        $this->logActivity(
            $request,
            'user',
            $user->id,
            null,
            $request->user(),
            'create',
            'User created: ' . ($user->email ?: ('User #' . $user->id)),
            [
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
            ]
        );

        if (in_array($validated['role'], ['client'], true)) {
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
        $snapshot = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
        ];
        $user->delete();

        $this->logActivity(
            $request,
            'user',
            $user->id,
            null,
            $request->user(),
            'delete',
            'User deleted: ' . ($display ?: ('User #' . $user->id)),
            ['before' => $snapshot]
        );

        return back()->with('status', "User {$display} deleted successfully.");
    }

    public function adminClientDestroy(Request $request, Client $client): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $clientName = $client->name ?: ('Client #' . $client->id);
        $snapshot = $client->only(['name', 'email', 'phone', 'company', 'status']);
        $client->delete();

        $this->logActivity(
            $request,
            'client',
            $client->id,
            $client->id,
            $request->user(),
            'delete',
            'Client deleted: ' . $clientName,
            ['before' => $snapshot]
        );

        return redirect()->route('admin.clients.index')->with('status', "{$clientName} deleted successfully.");
    }

    public function adminClientShow(Request $request, Client $client): View
    {
        $client->load([
            'projects' => function ($query): void {
                $query->latest('scheduled_at')
                    ->latest('id')
                    ->withCount([
                        'media as gallery_media_count' => function ($mediaQuery): void {
                            $mediaQuery->whereIn('type', ['image', 'video']);
                        },
                        'media as final_zip_count' => function ($mediaQuery): void {
                            $mediaQuery->where('type', 'final_zip');
                        },
                        'invoices',
                        'serviceRequests',
                        'bookingRequests',
                        'comments',
                    ])
                    ->with([
                        'creator:id,name,email',
                        'invoices:id,client_project_id,status,amount,currency,due_date',
                        'assignments.user:id,name,email,role',
                    ]);
            },
        ]);

        return view('admin.client-show', [
            'client' => $client,
            'projectStatuses' => ['accepted', 'shooting', 'editing', 'complete'],
        ]);
    }

    public function adminClientExport(Request $request, Client $client)
    {
        $this->ensureOwnerAdminAccess($request);

        $client->load([
            'projects' => function ($query): void {
                $query->with([
                    'assignments.user:id,name,email,role',
                    'media',
                    'comments.user:id,name,email,role',
                    'tasks.assignee:id,name,email,role',
                    'tasks.creator:id,name,email,role',
                    'invoices.payments',
                ])->withCount([
                    'invoices',
                    'serviceRequests',
                    'bookingRequests',
                    'comments',
                ]);
            },
            'invoices.payments',
            'messages',
            'serviceRequests',
            'bookingRequests',
        ]);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'client' => $client->toArray(),
        ];

        $filename = 'client-export-' . $client->id . '-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }, $filename);
    }

    public function adminClientAnonymize(Request $request, Client $client): RedirectResponse
    {
        $this->ensureOwnerAdminAccess($request);

        $suffix = 'deleted+' . $client->id . '@example.invalid';
        $placeholderName = 'Deleted Client #' . $client->id;

        if ($client->user) {
            $client->user->name = $placeholderName;
            $client->user->email = $suffix;
            $client->user->phone = null;
            $client->user->password = 'Deleted@' . strtoupper(Str::random(12));
            $client->user->save();
        }

        $client->name = $placeholderName;
        $client->email = $suffix;
        $client->phone = null;
        $client->company = null;
        $client->notes = 'Client anonymized on ' . now()->format('Y-m-d H:i:s');
        $client->notify_portal = false;
        $client->notify_invoice_email = false;
        $client->save();

        if (Schema::hasTable('client_messages')) {
            DB::table('client_messages')
                ->where('client_id', $client->id)
                ->where('sender_role', 'client')
                ->update([
                    'sender_user_id' => null,
                    'message' => '[redacted]',
                ]);
        }

        if (Schema::hasTable('client_project_comments')) {
            $projectIds = $client->projects()->pluck('id')->all();
            if ($projectIds !== []) {
                DB::table('client_project_comments')
                    ->whereIn('client_project_id', $projectIds)
                    ->where('sender_role', 'client')
                    ->update([
                        'user_id' => null,
                        'body' => '[redacted]',
                    ]);
            }
        }

        if (Schema::hasTable('client_service_requests')) {
            DB::table('client_service_requests')
                ->where('client_id', $client->id)
                ->update([
                    'subject' => null,
                    'details' => null,
                ]);
        }

        if (Schema::hasTable('booking_requests')) {
            DB::table('booking_requests')
                ->where('client_id', $client->id)
                ->update([
                    'notes' => null,
                    'preferred_time_window' => null,
                    'alternate_slots' => null,
                ]);
        }

        if (Schema::hasTable('client_invoices')) {
            DB::table('client_invoices')
                ->where('client_id', $client->id)
                ->update([
                    'notes' => null,
                ]);
        }

        $this->logActivity(
            $request,
            'client',
            $client->id,
            $client->id,
            $request->user(),
            'update',
            'Client anonymized'
        );

        return back()->with('status', 'Client anonymized successfully.');
    }

        public function adminClientMessagesIndex(Request $request): View
    {
        $mode = strtolower(trim((string) $request->string('mode', 'clients')));
        $mode = in_array($mode, ['clients', 'users'], true) ? $mode : 'clients';
        $role = strtolower(trim((string) $request->user()?->role));
        $canViewAllChats = in_array($role, ['admin', 'owner'], true);
        if (!$canViewAllChats) {
            $mode = 'users';
        }

        if ($mode === 'users') {
            $search = trim((string) $request->string('search'));
            $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
            $adminId = (int) ($request->user()?->id ?? 0);

            $users = User::query()
                ->when(!$canViewAllChats, function ($query): void {
                    $query->whereIn('role', $this->adminRoles());
                }, function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->whereNull('role')
                            ->orWhereRaw('lower(role) != ?', ['client']);
                    });
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get();

            $userIds = $users->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $adminIds = $userIds;

            $threadMessages = UserMessage::query()
                ->with([
                    'sender:id,name,email,role',
                    'recipient:id,name,email,role',
                ])
                ->where(function ($query) use ($adminId, $adminIds, $canViewAllChats): void {
                    if ($canViewAllChats) {
                        $query->where('sender_user_id', $adminId)
                            ->orWhere('recipient_user_id', $adminId);
                        return;
                    }
                    $query->where(function ($inner) use ($adminId, $adminIds): void {
                        $inner->where('sender_user_id', $adminId)
                            ->whereIn('recipient_user_id', $adminIds);
                    })->orWhere(function ($inner) use ($adminId, $adminIds): void {
                        $inner->where('recipient_user_id', $adminId)
                            ->whereIn('sender_user_id', $adminIds);
                    });
                })
                ->latest('sent_at')
                ->latest('id')
                ->get();

            $threadSummaries = collect(array_values($threadMessages->reduce(function (array $carry, UserMessage $message) use ($adminId, $userIds): array {
                $otherId = (int) ($message->sender_user_id === $adminId ? $message->recipient_user_id : $message->sender_user_id);
                if (!in_array($otherId, $userIds, true)) {
                    return $carry;
                }
                if (!isset($carry[$otherId])) {
                    $message->thread_user_id = $otherId;
                    $carry[$otherId] = $message;
                }
                return $carry;
            }, [])));

            if ($userId === null || $userId <= 0) {
                $userId = (int) ($threadSummaries->first()?->thread_user_id ?: 0);
            }

            if ($userId <= 0) {
                $userId = (int) ($users->first()?->id ?: 0);
            }

            $activeUser = $userId > 0 ? User::query()->find($userId) : null;
            $activeMessages = collect();
            if ($activeUser) {
                $activeMessages = UserMessage::query()
                    ->with([
                        'sender:id,name,email,role',
                        'recipient:id,name,email,role',
                    ])
                    ->where(function ($query) use ($adminId, $activeUser): void {
                        $query->where('sender_user_id', $adminId)
                            ->where('recipient_user_id', $activeUser->id);
                    })
                    ->orWhere(function ($query) use ($adminId, $activeUser): void {
                        $query->where('sender_user_id', $activeUser->id)
                            ->where('recipient_user_id', $adminId);
                    })
                    ->latest('sent_at')
                    ->latest('id')
                    ->limit(200)
                    ->get()
                    ->sortBy(function (UserMessage $message): array {
                        return [
                            optional($message->sent_at)->timestamp ?? optional($message->created_at)->timestamp ?? 0,
                            $message->id,
                        ];
                    })
                    ->values();
            }

            $totalMessages = UserMessage::query()
                ->where(function ($query) use ($adminId): void {
                    $query->where('sender_user_id', $adminId)
                        ->orWhere('recipient_user_id', $adminId);
                })
                ->count();

            return view('admin.client-messages-index', [
                'can_view_all_chats' => $canViewAllChats,
                'mode' => 'users',
                'clients' => collect(),
                'users' => $users,
                'threadSummaries' => $threadSummaries,
                'activeClient' => null,
                'activeUser' => $activeUser,
                'activeMessages' => $activeMessages,
                'messageStats' => [
                    'total_messages' => $totalMessages,
                    'client_threads' => $threadSummaries->count(),
                    'admin_sent' => UserMessage::query()->where('sender_user_id', $adminId)->count(),
                    'client_sent' => UserMessage::query()->where('recipient_user_id', $adminId)->count(),
                ],
                'statsLabels' => [
                    'threads' => 'User Threads',
                    'client_sent' => 'User Sent',
                ],
                'filters' => [
                    'search' => $search,
                    'user_id' => $activeUser?->id,
                    'sender_role' => '',
                ],
            ]);
        }

        $search = trim((string) $request->string('search'));
        $clientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;
        $senderRole = trim((string) $request->string('sender_role'));

        $baseMessagesQuery = ClientMessage::query()
            ->with([
                'client:id,name,email,status',
                'project:id,title,status',
                'sender:id,name,email',
            ]);

        $threadMessages = (clone $baseMessagesQuery)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('message', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function ($projectQuery) use ($search): void {
                            $projectQuery->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when($senderRole !== '', function ($query) use ($senderRole): void {
                $query->where('sender_role', $senderRole);
            })
            ->latest('sent_at')
            ->latest('id')
            ->get();

        $threadSummaries = $threadMessages
            ->filter(fn (ClientMessage $message): bool => $message->client_id !== null)
            ->unique('client_id')
            ->values();

        if ($clientId === null || $clientId <= 0) {
            $clientId = (int) ($threadSummaries->first()?->client_id ?: 0);
        }

        if ($clientId <= 0) {
            $clientId = (int) (Client::query()->orderBy('name')->value('id') ?: 0);
        }

        $activeClient = $clientId > 0
            ? Client::query()->with(['projects' => function ($query): void {
                $query->latest('id');
            }])->find($clientId)
            : null;

        $activeMessages = collect();
        if ($activeClient) {
            $activeMessages = ClientMessage::query()
                ->with([
                    'client:id,name,email,status',
                    'project:id,title,status',
                    'sender:id,name,email',
                ])
                ->where('client_id', $activeClient->id)
                ->latest('sent_at')
                ->latest('id')
                ->limit(200)
                ->get()
                ->sortBy(function (ClientMessage $message): array {
                    return [
                        optional($message->sent_at)->timestamp ?? optional($message->created_at)->timestamp ?? 0,
                        $message->id,
                    ];
                })
                ->values();
        }

        $clients = Client::query()
            ->with(['projects' => function ($query): void {
                $query->latest('id');
            }])
            ->orderBy('name')
            ->get();

        return view('admin.client-messages-index', [
            'can_view_all_chats' => $canViewAllChats,
            'mode' => 'clients',
            'clients' => $clients,
            'users' => collect(),
            'threadSummaries' => $threadSummaries,
            'activeClient' => $activeClient,
            'activeUser' => null,
            'activeMessages' => $activeMessages,
            'messageStats' => [
                'total_messages' => ClientMessage::query()->count(),
                'client_threads' => ClientMessage::query()->distinct('client_id')->count('client_id'),
                'admin_sent' => ClientMessage::query()->where('sender_role', 'admin')->count(),
                'client_sent' => ClientMessage::query()->where('sender_role', 'client')->count(),
            ],
            'statsLabels' => [
                'threads' => 'Client Threads',
                'client_sent' => 'Client Sent',
            ],
            'filters' => [
                'search' => $search,
                'client_id' => $activeClient?->id,
                'sender_role' => $senderRole,
            ],
        ]);
    }
public function adminClientMessagesCenterStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'client_project_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $client = Client::query()->findOrFail((int) $validated['client_id']);
        $projectId = null;
        if (!blank($validated['client_project_id'] ?? null)) {
            $projectId = ClientProject::query()
                ->where('client_id', $client->id)
                ->where('id', (int) $validated['client_project_id'])
                ->value('id');

            if ($projectId === null) {
                return back()->withErrors([
                    'client_project_id' => 'Selected project does not belong to this client.',
                ])->withInput();
            }
        }

        ClientMessage::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'sender_user_id' => $request->user()?->id,
            'sender_role' => 'admin',
            'message' => $validated['message'],
            'sent_at' => now(),
        ]);

        $this->logActivity(
            $request,
            'message',
            $client->id,
            $client->id,
            $request->user(),
            'send',
            'Client message sent: ' . ($client->name ?: ('Client #' . $client->id)),
            [
                'project_id' => $projectId,
                'preview' => mb_strimwidth($validated['message'], 0, 140, '...'),
            ]
        );

        $this->notifyClientUser(
            $client,
            'new_admin_message',
            'New message from admin',
            mb_strimwidth($validated['message'], 0, 140, '...'),
            route('user.messages.index')
        );

        return redirect()->route('admin.messages.index', ['client_id' => $client->id])->with('status', 'Message sent successfully.');
    }

    public function adminUserMessagesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $adminId = (int) ($request->user()?->id ?? 0);
        $recipientId = (int) $validated['recipient_user_id'];

        if ($recipientId === $adminId) {
            return back()->withErrors([
                'recipient_user_id' => 'Please choose a different user.',
            ])->withInput();
        }

        UserMessage::create([
            'sender_user_id' => $adminId,
            'recipient_user_id' => $recipientId,
            'message' => $validated['message'],
            'sent_at' => now(),
        ]);

        $this->logActivity(
            $request,
            'message',
            $recipientId,
            null,
            $request->user(),
            'send',
            'Internal message sent to user #' . $recipientId,
            [
                'preview' => mb_strimwidth($validated['message'], 0, 140, '...'),
            ]
        );

        $senderName = (string) ($request->user()?->name ?? 'Admin');
        $messagePreview = mb_strimwidth($validated['message'], 0, 140, '...');
        $this->notificationService()->notifyUser(
            $recipientId,
            'direct_message_received',
            "New message from {$senderName}",
            $messagePreview,
            route('admin.messages.index', ['mode' => 'users', 'user_id' => $adminId]),
            ['sender_id' => $adminId]
        );

        return redirect()->route('admin.messages.index', [
            'mode' => 'users',
            'user_id' => $recipientId,
        ])->with('status', 'Message sent successfully.');
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
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project = ClientProject::create([
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

        $assignmentIds = $this->sanitizeAssignableUserIds((array) ($validated['assigned_user_ids'] ?? []));
        if ($assignmentIds !== []) {
            $project->assignedUsers()->syncWithPivotValues($assignmentIds, [
                'assigned_by' => $request->user()?->id,
            ]);
        }

        $internalActionUrl = $this->projectInternalActionUrl($project);
        $this->notifyProjectAssignees(
            $project,
            'project_assigned',
            'New project assigned',
            "Project \"{$project->title}\" has been created and assigned.",
            $internalActionUrl,
            ['project_id' => $project->id, 'client_id' => $project->client_id],
            (int) ($request->user()?->id ?? 0),
            true
        );

        $this->notifyClientUser(
            $client,
            'project_created',
            'New project created',
            "Your project \"{$project->title}\" has been created.",
            route('user.projects.show', $project),
            ['project_id' => $project->id]
        );

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'create',
            'Project created: ' . ($project->title ?: ('Project #' . $project->id)),
            [
                'after' => [
                    'title' => $project->title,
                    'status' => $project->status,
                    'scheduled_at' => $project->scheduled_at,
                    'due_at' => $project->due_at,
                    'client_id' => $project->client_id,
                ],
            ]
        );

        return back()->with('status', 'Project created.');
    }

    public function adminClientProjectStatusUpdate(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,shooting,editing,complete'],
        ]);

        $previousStatus = $project->status;
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

        $internalActionUrl = $this->projectInternalActionUrl($project);
        $this->notifyProjectAssignees(
            $project,
            'project_status_updated_internal',
            'Project status updated',
            "Project \"{$project->title}\" is now {$project->status}.",
            $internalActionUrl,
            ['project_id' => $project->id, 'status' => $project->status],
            (int) ($request->user()?->id ?? 0),
            true
        );

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'status_update',
            'Project status updated: ' . ($project->title ?: ('Project #' . $project->id)),
            [
                'before' => ['status' => $previousStatus],
                'after' => ['status' => $project->status],
            ]
        );

        app(OutboundWebhookService::class)->send('project.status_updated', [
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'status' => $project->status,
            'previous_status' => $previousStatus,
        ]);

        return back()->with('status', 'Project status updated.');
    }

    public function adminProjectAssignmentsUpdate(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $previousAssignmentIds = $project->assignedUsers()
            ->pluck('users.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $assignmentIds = $this->sanitizeAssignableUserIds((array) ($validated['assigned_user_ids'] ?? []));
        $project->assignedUsers()->syncWithPivotValues($assignmentIds, [
            'assigned_by' => $request->user()?->id,
        ]);

        $newAssigneeIds = array_values(array_diff($assignmentIds, $previousAssignmentIds));
        if ($newAssigneeIds !== []) {
            $actorId = (int) ($request->user()?->id ?? 0);
            $internalActionUrl = $this->projectInternalActionUrl($project);
            $messageBody = "You have been assigned to project \"{$project->title}\".";

            $managerIds = User::query()
                ->whereIn('role', ['owner', 'admin', 'manager'])
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $recipientIds = array_values(array_unique(array_merge($newAssigneeIds, $managerIds)));
            if ($actorId > 0) {
                $recipientIds = array_values(array_filter($recipientIds, static fn ($id): bool => (int) $id !== $actorId));
            }

            foreach ($recipientIds as $userId) {
                $this->notificationService()->notifyUser(
                    (int) $userId,
                    'project_assigned',
                    'Project assigned',
                    $messageBody,
                    $internalActionUrl,
                    ['project_id' => $project->id, 'client_id' => $project->client_id]
                );
            }
        }

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'update',
            'Project assignments updated: ' . ($project->title ?: ('Project #' . $project->id)),
            [
                'before' => ['assigned_user_ids' => $previousAssignmentIds],
                'after' => ['assigned_user_ids' => $assignmentIds],
            ]
        );

        return back()->with('status', 'Assigned team updated.');
    }

    public function adminProjectTaskStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $task = ProjectTask::create([
            'client_project_id' => $project->id,
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
            'due_date' => $validated['due_date'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        if ($task->assigned_to) {
            $this->notificationService()->notifyUser(
                (int) $task->assigned_to,
                'project_task_assigned',
                'New task assigned',
                $task->title,
                route('admin.projects.workspace', $project),
                ['project_id' => $project->id, 'task_id' => $task->id]
            );
        }

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'create',
            'Project task created: ' . $task->title,
            ['task_id' => $task->id]
        );

        app(OutboundWebhookService::class)->send('project.task_created', [
            'task_id' => $task->id,
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'title' => $task->title,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateString(),
            'assigned_to' => $task->assigned_to,
        ]);

        return back()->with('status', 'Task added.');
    }

    public function adminProjectTaskUpdate(Request $request, ClientProject $project, ProjectTask $task): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        if ((int) $task->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,blocked,done'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $previousStatus = $task->status;
        $task->status = $validated['status'];
        $task->assigned_to = $validated['assigned_to'] ?? null;
        $task->due_date = $validated['due_date'] ?? null;
        if ($task->status === 'done') {
            $task->completed_at = now();
            $task->completed_by = $request->user()?->id;
        } else {
            $task->completed_at = null;
            $task->completed_by = null;
        }
        $task->save();

        if ($task->assigned_to) {
            $this->notificationService()->notifyUser(
                (int) $task->assigned_to,
                'project_task_updated',
                'Task updated',
                $task->title,
                route('admin.projects.workspace', $project),
                ['project_id' => $project->id, 'task_id' => $task->id]
            );
        }

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'update',
            'Project task updated: ' . $task->title,
            [
                'task_id' => $task->id,
                'before' => ['status' => $previousStatus],
                'after' => ['status' => $task->status],
            ]
        );

        app(OutboundWebhookService::class)->send('project.task_updated', [
            'task_id' => $task->id,
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'status' => $task->status,
            'previous_status' => $previousStatus,
            'due_date' => $task->due_date?->toDateString(),
            'assigned_to' => $task->assigned_to,
        ]);

        return back()->with('status', 'Task updated.');
    }

    public function adminProjectTaskDestroy(Request $request, ClientProject $project, ProjectTask $task): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        if ((int) $task->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $title = $task->title;
        $task->delete();

        $this->logActivity(
            $request,
            'project',
            $project->id,
            $project->client_id,
            $request->user(),
            'delete',
            'Project task deleted: ' . $title,
            ['task_title' => $title]
        );

        return back()->with('status', 'Task deleted.');
    }

    public function adminProjectCommentStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureInternalUserCanCommentOnProject($request, $project);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'parent_comment_id' => ['nullable', 'integer', 'exists:client_project_comments,id'],
        ]);

        $parentCommentId = $validated['parent_comment_id'] ?? null;
        if ($parentCommentId) {
            $parentComment = ClientProjectComment::query()
                ->where('id', $parentCommentId)
                ->where('client_project_id', $project->id)
                ->first();
            if (!$parentComment) {
                abort(404);
            }
        }

        $comment = ClientProjectComment::query()->create([
            'client_project_id' => $project->id,
            'parent_comment_id' => $parentCommentId,
            'user_id' => $request->user()?->id,
            'sender_role' => strtolower(trim((string) ($request->user()?->role ?: 'admin'))),
            'body' => $validated['body'],
        ]);

        if ($project->client) {
            $this->notifyClientUser(
                $project->client,
                'project_comment_added',
                'New project update',
                mb_strimwidth($validated['body'], 0, 140, '...'),
                route('user.projects.show', $project)
            );
        }

        $internalActionUrl = $this->projectInternalActionUrl($project);
        $commentAuthor = (string) ($request->user()?->name ?? 'Team member');
        $commentPreview = mb_strimwidth($validated['body'], 0, 140, '...');
        $this->notifyProjectAssignees(
            $project,
            'project_comment_added_internal',
            'New project comment',
            "{$commentAuthor}: {$commentPreview}",
            $internalActionUrl,
            ['project_id' => $project->id, 'comment_id' => $comment->id],
            (int) ($request->user()?->id ?? 0),
            true
        );

        return back()->with('status', 'Project comment posted.');
    }

    public function adminProjectCommentDestroy(Request $request, ClientProject $project, ClientProjectComment $comment): RedirectResponse
    {
        $this->ensureInternalUserCanCommentOnProject($request, $project);

        if ((int) $comment->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $role = strtolower(trim((string) $request->user()?->role));
        $canDeleteAny = in_array($role, ['owner', 'admin', 'manager'], true);

        if (!$canDeleteAny && (int) $comment->user_id !== (int) ($request->user()?->id ?? 0)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('status', 'Project comment deleted.');
    }

    public function adminProjectCommentUpdate(Request $request, ClientProject $project, ClientProjectComment $comment): RedirectResponse
    {
        $this->ensureInternalUserCanCommentOnProject($request, $project);

        if ((int) $comment->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $role = strtolower(trim((string) $request->user()?->role));
        $canEditAny = in_array($role, ['owner', 'admin', 'manager'], true);

        if (!$canEditAny && (int) $comment->user_id !== (int) ($request->user()?->id ?? 0)) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
            'edited_at' => now(),
        ]);

        return back()->with('status', 'Project comment updated.');
    }
    public function adminProjectMediaStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureInternalUserCanUploadProjectMedia($request, $project);

        $validated = $request->validate([
            'media_stage' => ['required', 'string', 'in:raw,edited'],
            'media_files' => ['required', 'array', 'min:1'],
            'media_files.*' => ['required', 'file', 'max:512000'],
        ]);

        $mediaStage = (string) ($validated['media_stage'] ?? 'raw');
        $saved = 0;
        $projectMediaBasePath = $this->projectMediaBasePath($project);
        $galleryUploadPath = $this->projectMediaUploadPath($project, $request->user(), $this->projectMediaBucketForStage($mediaStage));
        $mediaDisk = $this->resolveMediaDisk();
        $watermarkSettings = $this->getWatermarkSettings();
        $watermarkRenderConfig = $this->resolveWatermarkRenderConfig($watermarkSettings);
        $watermarkSignature = (string) ($watermarkRenderConfig['signature'] ?? '');

        foreach (($validated['media_files'] ?? []) as $file) {
            $mimeType = (string) ($file->getClientMimeType() ?: '');
            $type = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'other');
            if (!in_array($type, ['image', 'video'], true)) {
                continue;
            }

            $storedPath = $file->store($galleryUploadPath, $mediaDisk);
            if (!$storedPath) {
                continue;
            }

            $watermarkDisk = null;
            $watermarkPath = null;
            $mediaWatermarkSignature = null;
            if ($type === 'image') {
                $watermarked = $this->generateHardWatermarkVariant($mediaDisk, $storedPath, $projectMediaBasePath, $watermarkRenderConfig);
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
                'delivery_stage' => $mediaStage,
                'disk' => $mediaDisk,
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

        $stageLabel = $mediaStage === 'edited' ? 'edited/final media' : 'raw footage media';

        $this->logActivity(
            $request,
            'media',
            $project->id,
            $project->client_id,
            $request->user(),
            'upload',
            "Uploaded {$saved} {$stageLabel} file(s) to project: " . ($project->title ?: ('Project #' . $project->id)),
            [
                'stage' => $mediaStage,
                'count' => $saved,
            ]
        );

        return back()->with('status', "{$saved} {$stageLabel} file(s) uploaded.");
    }

    public function adminProjectRawZipStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureInternalUserCanUploadProjectMedia($request, $project);

        $validated = $request->validate([
            'raw_zip' => ['required', 'file', 'mimes:zip', 'max:1024000'],
        ]);

        $file = $validated['raw_zip'];
        $mediaDisk = $this->resolveMediaDisk();
        $storedPath = $file->store($this->projectMediaUploadPath($project, $request->user(), 'raw-zip'), $mediaDisk);

        $mediaItem = ClientProjectMedia::create([
            'client_project_id' => $project->id,
            'uploaded_by' => $request->user()?->id,
            'type' => 'raw_zip',
            'delivery_stage' => 'raw',
            'disk' => $mediaDisk,
            'path' => $storedPath,
            'original_name' => (string) ($file->getClientOriginalName() ?: basename($storedPath)),
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/zip'),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->logActivity(
            $request,
            'media',
            $mediaItem->id,
            $project->client_id,
            $request->user(),
            'upload',
            'Raw ZIP uploaded: ' . ($mediaItem->original_name ?: ('Media #' . $mediaItem->id)),
            [
                'project_id' => $project->id,
                'type' => 'raw_zip',
            ]
        );

        return back()->with('status', 'Raw footage ZIP uploaded.');
    }

    public function adminProjectDeliveryZipStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureInternalUserCanUploadProjectMedia($request, $project);

        $validated = $request->validate([
            'delivery_zip' => ['required', 'file', 'mimes:zip', 'max:1024000'],
        ]);

        $file = $validated['delivery_zip'];
        $mediaDisk = $this->resolveMediaDisk();
        $storedPath = $file->store($this->projectMediaUploadPath($project, $request->user(), 'delivery'), $mediaDisk);

        $mediaItem = ClientProjectMedia::create([
            'client_project_id' => $project->id,
            'uploaded_by' => $request->user()?->id,
            'type' => 'final_zip',
            'delivery_stage' => 'final_zip',
            'disk' => $mediaDisk,
            'path' => $storedPath,
            'original_name' => (string) ($file->getClientOriginalName() ?: basename($storedPath)),
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/zip'),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->logActivity(
            $request,
            'media',
            $mediaItem->id,
            $project->client_id,
            $request->user(),
            'upload',
            'Final ZIP uploaded: ' . ($mediaItem->original_name ?: ('Media #' . $mediaItem->id)),
            [
                'project_id' => $project->id,
                'type' => 'final_zip',
            ]
        );

        return back()->with('status', 'Final delivery ZIP uploaded.');
    }

    public function adminProjectMediaView(Request $request, ClientProject $project, ClientProjectMedia $media)
    {
        $this->ensureInternalUserCanAccessAssignedProject($request, $project);

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

        $isZip = in_array((string) $media->type, ['raw_zip', 'final_zip'], true)
            || Str::endsWith(strtolower((string) $media->original_name), '.zip');

        if ($isZip) {
            $downloadName = trim((string) $media->original_name) !== ''
                ? (string) $media->original_name
                : basename($path);

            return response()->download($absolutePath, $downloadName, [
                'Content-Type' => $mimeType,
            ]);
        }

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
        if ((int) $media->client_project_id !== (int) $project->id) {
            abort(404);
        }

        $role = strtolower(trim((string) $request->user()?->role));
        if (!in_array($role, ['owner', 'admin', 'manager'], true)) {
            abort_unless(in_array($role, ['photographer', 'editor'], true), 403);
            $this->ensureInternalUserCanAccessAssignedProject($request, $project);
            abort_unless((int) ($media->uploaded_by ?? 0) === (int) ($request->user()?->id ?? 0), 403);
        }

        $displayName = trim((string) $media->original_name) !== ''
            ? (string) $media->original_name
            : ('Media #' . $media->id);
        $snapshot = $media->only(['id', 'type', 'delivery_stage', 'disk', 'path', 'original_name', 'size_bytes']);

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

        $this->logActivity(
            $request,
            'media',
            $media->id,
            $project->client_id,
            $request->user(),
            'delete',
            'Media deleted: ' . $displayName,
            ['before' => $snapshot, 'project_id' => $project->id]
        );

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

        $this->logActivity(
            $request,
            'media',
            $media->id,
            $project->client_id,
            $request->user(),
            'download',
            'Media downloaded by client: ' . ($media->original_name ?: ('Media #' . $media->id)),
            ['project_id' => $project->id]
        );

        return Storage::disk($media->disk)->download($media->path, $media->original_name);
    }

    public function userProjectMediaZipDownload(Request $request, ClientProject $project): StreamedResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        if (!$this->projectIsPaid($project)) {
            abort(403, 'Project is not paid yet. Downloads are locked.');
        }

        $finalZip = $project->media()
            ->where('type', 'final_zip')
            ->latest('id')
            ->first();

        if ($finalZip instanceof ClientProjectMedia) {
            if (!Storage::disk($finalZip->disk)->exists($finalZip->path)) {
                abort(404, 'Final delivery ZIP is not available right now.');
            }

            $downloadName = trim((string) $finalZip->original_name) !== ''
                ? (string) $finalZip->original_name
                : ('project-' . $project->id . '-delivery.zip');

            $this->logActivity(
                $request,
                'media',
                $finalZip->id,
                $project->client_id,
                $request->user(),
                'download',
                'Final delivery ZIP downloaded by client: ' . ($finalZip->original_name ?: ('Media #' . $finalZip->id)),
                ['project_id' => $project->id]
            );

            return Storage::disk($finalZip->disk)->download($finalZip->path, $downloadName);
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
        $this->ensurePipelineWriteAccess($request);

        $allowedCurrencies = $this->resolveAllowedCurrencyCodes();
        $currencyRule = ['required', 'string', 'max:10'];
        if ($allowedCurrencies !== []) {
            $currencyRule[] = Rule::in($allowedCurrencies);
        }

        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => $currencyRule,
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

            if ($projectId === null) {
                return back()->withErrors([
                    'client_project_id' => 'Selected project does not belong to this client.',
                ])->withInput();
            }
        }

        try {
            $invoice = ClientInvoice::create([
                'client_id' => $client->id,
                'client_project_id' => $projectId,
                'created_by' => $request->user()?->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => round((float) $validated['amount'], 2),
                'amount_paid' => 0,
                'balance_due' => round((float) $validated['amount'], 2),
                'currency' => strtoupper(trim((string) $validated['currency'])),
                'status' => $validated['status'],
                'issued_at' => $issuedAt,
                'due_date' => $dueDate,
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($validated['status'] === 'paid') {
                $this->recordInvoicePayment(
                    $invoice,
                    (float) $invoice->amount,
                    $request->user(),
                    'manual',
                    'manual-' . now()->timestamp,
                    'manual',
                    ['note' => 'Marked paid on creation.']
                );
            }

            $this->notifyClientUser(
                $client,
                'invoice_created',
                'New invoice created',
                "Invoice {$invoice->invoice_number} has been created.",
                route('user.dashboard'),
                ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]
            );

            $settings = $this->resolveInvoiceSettings();
            if (
                (bool) ($settings->auto_email_on_invoice_create ?? true)
                && in_array($invoice->status, ['sent', 'partial', 'overdue'], true)
            ) {
                app(InvoiceEmailService::class)->sendInvoiceCreated($invoice, $client, $request->user());
            }

            $this->logActivity(
                $request,
                'invoice',
                $invoice->id,
                $invoice->client_id,
                $request->user(),
                'create',
                'Invoice created: ' . $invoice->invoice_number,
                [
                    'after' => [
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency,
                        'status' => $invoice->status,
                        'issued_at' => $invoice->issued_at,
                        'due_date' => $invoice->due_date,
                    ],
                ]
            );

            app(OutboundWebhookService::class)->send('invoice.created', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->client_project_id,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
            ]);
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

            if ($projectId === null) {
                return back()->withErrors([
                    'client_project_id' => 'Selected project does not belong to this client.',
                ])->withInput();
            }
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

        $previousStatus = (string) $invoice->status;
        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,partial,paid,overdue'],
        ]);

        if ($validated['status'] === 'paid') {
            $this->recordInvoicePayment(
                $invoice,
                (float) ($invoice->balance_due ?? $invoice->amount),
                $request->user(),
                'manual',
                'manual-' . now()->timestamp,
                'manual',
                ['note' => 'Marked paid by admin.']
            );
        } else {
            $invoice->status = $validated['status'];
            if ($validated['status'] !== 'paid') {
                $invoice->paid_at = null;
            }
            $invoice->save();
        }

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

            $settings = $this->resolveInvoiceSettings();
            if (
                (bool) ($settings->auto_email_on_invoice_create ?? true)
                && in_array($invoice->status, ['sent', 'partial', 'overdue'], true)
                && $previousStatus !== $invoice->status
            ) {
                app(InvoiceEmailService::class)->sendInvoiceCreated($invoice, $invoice->client, $request->user());
            }
        }

        $this->logActivity(
            $request,
            'invoice',
            $invoice->id,
            $invoice->client_id,
            $request->user(),
            'status_update',
            'Invoice status updated: ' . $invoice->invoice_number,
            [
                'before' => ['status' => $previousStatus],
                'after' => ['status' => $invoice->status],
            ]
        );

        return back()->with('status', "Invoice {$invoice->invoice_number} updated.");
    }

    public function adminInvoicePaymentStore(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:manual,stripe,paypal,bank_transfer,cheque,cash,other'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $amount = (float) $validated['amount'];
        $invoice->refresh();
        $balanceDue = (float) ($invoice->balance_due ?? $invoice->amount);
        if ($amount > $balanceDue) {
            return back()->withErrors(['amount' => 'Payment amount cannot exceed balance due.'])->withInput();
        }

        $method = (string) $validated['method'];
        $provider = in_array($method, ['stripe', 'paypal'], true) ? $method : 'manual';

        $this->recordInvoicePayment(
            $invoice,
            $amount,
            $request->user(),
            $provider,
            $validated['reference'] ?? null,
            $method,
            ['source' => 'admin_manual_entry']
        );

        return back()->with('status', 'Payment recorded.');
    }

    public function adminInvoiceDestroy(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $invoiceNumber = (string) $invoice->invoice_number;
        $snapshot = $invoice->only(['invoice_number', 'amount', 'currency', 'status', 'issued_at', 'due_date', 'client_id']);
        $invoice->delete();

        $this->logActivity(
            $request,
            'invoice',
            $invoice->id,
            $invoice->client_id,
            $request->user(),
            'delete',
            'Invoice deleted: ' . $invoiceNumber,
            ['before' => $snapshot]
        );

        return back()->with('status', "Invoice {$invoiceNumber} deleted successfully.");
    }

    public function adminInvoicePdfDownload(Request $request, ClientInvoice $invoice)
    {
        $this->ensurePipelineWriteAccess($request);

        $invoice->loadMissing([
            'client:id,user_id,name,email,phone,company',
            'project:id,title,service_type,property_address',
        ]);

        $this->logActivity(
            $request,
            'invoice',
            $invoice->id,
            $invoice->client_id,
            $request->user(),
            'download',
            'Invoice PDF downloaded: ' . $invoice->invoice_number
        );

        $clientName = trim((string) ($invoice->client?->name ?? 'client'));
        $safeName = Str::slug($clientName !== '' ? $clientName : 'client');
        $filename = 'invoice-' . $invoice->invoice_number . '-' . $safeName . '.pdf';

        $settings = $this->resolveInvoiceSettings();
        $subtotal = round((float) $invoice->amount, 2);
        $includeTax = (bool) $settings->include_tax_on_pdf;
        $taxRate = max(0.0, (float) $settings->tax_rate_percent);
        $taxAmount = $includeTax ? round(($subtotal * $taxRate) / 100, 2) : 0.0;
        $total = round($subtotal + $taxAmount, 2);

        $logoAbsolutePath = null;
        foreach ([
            public_path('assets/media/logo.png'),
            public_path('media/logo.png'),
            storage_path('app/public/media/logo.png'),
        ] as $candidatePath) {
            if (is_string($candidatePath) && $candidatePath !== '' && file_exists($candidatePath)) {
                $logoAbsolutePath = $candidatePath;
                break;
            }
        }

        $pdf = Pdf::loadView('admin.pdf.invoice', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'project' => $invoice->project,
            'brandName' => 'Maccento Real Estate Media',
            'brandPhone' => '+1 (514) 951-9141',
            'brandEmail' => (string) config('mail.from.address', 'info@maccento.ca'),
            'subtotal' => $subtotal,
            'includeTax' => $includeTax,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'logoAbsolutePath' => $logoAbsolutePath,
        ]);

        return $pdf->download($filename);
    }

    public function adminBookingRequestStatusUpdate(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:new,proposed,confirmed,rescheduled,cancelled,closed'],
            'scheduled_at' => ['nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:400'],
        ]);

        $previousStatus = (string) $bookingRequest->status;
        $bookingRequest->status = $validated['status'];
        $bookingRequest->save();

        $bookingRequest->loadMissing([
            'client',
            'project:id,title,scheduled_at',
        ]);

        $scheduledAt = $validated['scheduled_at'] ?? null;
        if ($scheduledAt && $bookingRequest->project) {
            $bookingRequest->project->scheduled_at = $scheduledAt;
            $bookingRequest->project->save();
        }

        $timelineFragments = [];
        if ($previousStatus !== $bookingRequest->status) {
            $timelineFragments[] = 'Booking request status changed from ' . $previousStatus . ' to ' . $bookingRequest->status . '.';
        } else {
            $timelineFragments[] = 'Booking request status confirmed as ' . $bookingRequest->status . '.';
        }

        if ($scheduledAt && $bookingRequest->project) {
            $scheduledLabel = Carbon::parse((string) $scheduledAt)->format('Y-m-d H:i');
            $timelineFragments[] = 'Project schedule updated to ' . $scheduledLabel . '.';
        }

        $adminNote = trim((string) ($validated['admin_note'] ?? ''));
        if ($adminNote !== '') {
            $timelineFragments[] = $adminNote;
        }

        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [
            'action' => 'status_update',
            'before' => [
                'status' => $previousStatus,
            ],
            'after' => [
                'status' => $bookingRequest->status,
                'scheduled_at' => $scheduledAt,
            ],
            'note' => $adminNote ?: null,
        ]);

        ClientMessage::create([
            'client_id' => $bookingRequest->client_id,
            'client_project_id' => $bookingRequest->client_project_id,
            'sender_user_id' => $request->user()?->id,
            'sender_role' => 'admin',
            'message' => implode(' ', $timelineFragments),
            'sent_at' => now(),
        ]);

        if ($bookingRequest->client) {
            $this->notifyClientUser(
                $bookingRequest->client,
                'booking_request_status_updated',
                'Booking request updated',
                "Your booking request for {$bookingRequest->requested_service} is now {$bookingRequest->status}.",
                route('user.dashboard')
            );
        }

        return back()->with('status', 'Booking request updated.');
    }

    public function adminServiceRequestStatusUpdate(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $allowedCurrencies = $this->resolveAllowedCurrencyCodes();
        $invoiceCurrencyRule = ['nullable', 'string', 'max:10'];
        if ($allowedCurrencies !== []) {
            $invoiceCurrencyRule[] = Rule::in($allowedCurrencies);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:new,accepted,in_progress,completed,closed'],
            'create_invoice' => ['nullable', 'in:0,1'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0.01'],
            'invoice_currency' => $invoiceCurrencyRule,
            'invoice_due_date' => ['nullable', 'date'],
            'invoice_notes' => ['nullable', 'string', 'max:1500'],
            'timeline_note' => ['nullable', 'string', 'max:400'],
        ]);

        $createInvoice = (string) ($validated['create_invoice'] ?? '0') === '1';
        $invoice = null;
        if ($createInvoice) {
            if (!in_array($validated['status'], ['accepted', 'in_progress'], true)) {
                return back()->withErrors(['status' => 'To create an invoice, set status to Accepted or In Progress.'])->withInput();
            }

            $invoiceAmount = round((float) ($validated['invoice_amount'] ?? 0), 2);
            if ($invoiceAmount <= 0) {
                return back()->withErrors(['invoice_amount' => 'Invoice amount is required when creating an invoice.'])->withInput();
            }

            $invoiceCurrency = strtoupper(trim((string) ($validated['invoice_currency'] ?? 'USD')));
            $issuedAt = now()->toDateString();
            $dueDate = $validated['invoice_due_date'] ?? now()->addDays(7)->toDateString();

            if (strtotime((string) $dueDate) < strtotime((string) $issuedAt)) {
                return back()->withErrors(['invoice_due_date' => 'Invoice due date cannot be earlier than issue date.'])->withInput();
            }
        }

        $previousStatus = (string) $serviceRequest->status;
        $serviceRequest->status = $validated['status'];
        $serviceRequest->save();

        $serviceRequest->loadMissing([
            'client',
            'project:id,title,service_type',
        ]);

        if ($createInvoice) {
            $invoiceAmount = round((float) ($validated['invoice_amount'] ?? 0), 2);
            $invoiceCurrency = strtoupper(trim((string) ($validated['invoice_currency'] ?? 'USD')));
            $issuedAt = now()->toDateString();
            $dueDate = $validated['invoice_due_date'] ?? now()->addDays(7)->toDateString();

            $invoiceNotes = trim((string) ($validated['invoice_notes'] ?? ''));
            if ($invoiceNotes === '') {
                $invoiceNotes = 'Additional service request: ' . $serviceRequest->requested_service;
            }

            $invoice = ClientInvoice::create([
                'client_id' => $serviceRequest->client_id,
                'client_project_id' => $serviceRequest->client_project_id,
                'created_by' => $request->user()?->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => $invoiceAmount,
                'amount_paid' => 0,
                'balance_due' => $invoiceAmount,
                'currency' => $invoiceCurrency,
                'status' => 'sent',
                'issued_at' => $issuedAt,
                'due_date' => $dueDate,
                'paid_at' => null,
                'notes' => $invoiceNotes,
            ]);
        }

        $timelineFragments = [];
        if ($previousStatus !== $serviceRequest->status) {
            $timelineFragments[] = 'Service request status changed from ' . $previousStatus . ' to ' . $serviceRequest->status . '.';
        } else {
            $timelineFragments[] = 'Service request status confirmed as ' . $serviceRequest->status . '.';
        }

        if ($invoice) {
            $timelineFragments[] = 'Invoice ' . $invoice->invoice_number . ' created for this request.';
        }

        $timelineNote = trim((string) ($validated['timeline_note'] ?? ''));
        if ($timelineNote !== '') {
            $timelineFragments[] = $timelineNote;
        }

        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [
            'action' => 'status_update',
            'before' => [
                'status' => $previousStatus,
            ],
            'after' => [
                'status' => $serviceRequest->status,
            ],
            'create_invoice' => $createInvoice,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->invoice_number,
            'timeline_note' => $timelineNote ?: null,
        ]);

        ClientMessage::create([
            'client_id' => $serviceRequest->client_id,
            'client_project_id' => $serviceRequest->client_project_id,
            'sender_user_id' => $request->user()?->id,
            'sender_role' => 'admin',
            'message' => implode(' ', $timelineFragments),
            'sent_at' => now(),
        ]);

        if ($serviceRequest->client) {
            if ($invoice) {
                $this->notifyClientUser(
                    $serviceRequest->client,
                    'invoice_created',
                    'New invoice created',
                    "Invoice {$invoice->invoice_number} has been created for your additional service request.",
                    route('user.dashboard'),
                    ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]
                );
            }

            $this->notifyClientUser(
                $serviceRequest->client,
                'service_request_status_updated',
                'Service request updated',
                "Request \"{$serviceRequest->requested_service}\" is now {$serviceRequest->status}.",
                route('user.dashboard')
            );
        }

        if ($invoice) {
            return back()->with('status', 'Service request updated and invoice created.');
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

        $previousStatus = $lead->status;
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

        $this->logActivity(
            $request,
            'lead',
            $lead->id,
            $lead->client_id ?? null,
            $request->user(),
            'status_update',
            'Lead status updated: ' . ($lead->name ?: ('Lead #' . $lead->id)),
            [
                'before' => ['status' => $previousStatus],
                'after' => ['status' => $lead->status],
            ]
        );

        app(OutboundWebhookService::class)->send('lead.status_updated', [
            'lead_id' => $lead->id,
            'status' => $lead->status,
            'previous_status' => $previousStatus,
            'email' => $lead->email,
            'phone' => $lead->phone,
        ]);

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
        $snapshot = $lead->only(['name', 'email', 'phone', 'status', 'service_type', 'timeline']);
        $lead->delete();

        $this->logActivity(
            $request,
            'lead',
            $lead->id,
            $lead->client_id ?? null,
            $request->user(),
            'delete',
            'Lead deleted: ' . $leadLabel,
            ['before' => $snapshot]
        );

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

        $client = $this->resolvePortalClient($user, [
            'projects' => function ($query): void {
                $query->latest('scheduled_at')->latest('id')
                    ->limit(6)
                    ->withCount([
                        'media as gallery_media_count' => function ($mediaQuery): void {
                            $mediaQuery->whereIn('type', ['image', 'video']);
                        },
                        'media as final_zip_count' => function ($mediaQuery): void {
                            $mediaQuery->where('type', 'final_zip');
                        },
                        'messages',
                        'serviceRequests',
                    ])
                    ->with([
                        'media' => function ($mediaQuery): void {
                            $mediaQuery->latest('id');
                        },
                        'invoices' => function ($invoiceQuery): void {
                            $invoiceQuery->latest('id')->with('client:id,name,email,phone,company,user_id');
                        },
                    ]);
            },
            'invoices' => function ($query): void {
                $query->latest('id')
                    ->limit(6)
                    ->with([
                        'project:id,title,service_type,property_address',
                        'client:id,name,email,phone,company,user_id',
                    ]);
            },
            'messages' => function ($query): void {
                $query->latest('id')
                    ->limit(6)
                    ->with([
                        'sender:id,name,email',
                        'project:id,title,status',
                    ]);
            },
            'serviceRequests' => function ($query): void {
                $query->latest('id')
                    ->limit(6)
                    ->with('project:id,title,status');
            },
        ]);

        $leadQuery = $this->userLeadQuery($user);
        $quoteQuery = $this->userQuoteQuery($user);
        $portalStats = $this->buildUserPortalStats($client, $leadQuery, $quoteQuery);
        $leads = (clone $leadQuery)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'service_type', 'location', 'status', 'score']);
        $quotes = (clone $quoteQuery)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'quote_id', 'status', 'estimated_total', 'currency', 'submitted_at', 'services']);
        $recentProjects = $client?->projects ?? collect();
        $recentInvoices = $client?->invoices ?? collect();
        $recentMessages = $client?->messages ?? collect();

        return view('user.dashboard', [
            'client' => $client,
            'portalStats' => $portalStats,
            'leads' => $leads,
            'quotes' => $quotes,
            'recentProjects' => $recentProjects,
            'recentInvoices' => $recentInvoices,
            'recentMessages' => $recentMessages,
        ]);
    }

    public function userProjectsIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));

        if ($client) {
            $projects = ClientProject::query()
                ->where('client_id', $client->id)
                ->withCount([
                    'media as gallery_media_count' => function ($mediaQuery): void {
                        $mediaQuery->whereIn('type', ['image', 'video']);
                    },
                    'media as final_zip_count' => function ($mediaQuery): void {
                        $mediaQuery->where('type', 'final_zip');
                    },
                    'messages',
                    'serviceRequests',
                ])
                ->with([
                    'invoices:id,client_id,client_project_id,status,amount,currency,due_date',
                    'quoteBuild:id,quote_id,status',
                ])
                ->latest('scheduled_at')
                ->latest('id')
                ->paginate(8);
        } else {
            $projects = ClientProject::query()
                ->whereHas('assignments', function ($assignmentQuery) use ($user): void {
                    $assignmentQuery->where('user_id', $user?->id);
                })
                ->withCount([
                    'media as gallery_media_count' => function ($mediaQuery): void {
                        $mediaQuery->whereIn('type', ['image', 'video']);
                    },
                    'media as final_zip_count' => function ($mediaQuery): void {
                        $mediaQuery->where('type', 'final_zip');
                    },
                    'messages',
                    'serviceRequests',
                ])
                ->with([
                    'quoteBuild:id,quote_id,status',
                    'client:id,name,email',
                ])
                ->latest('scheduled_at')
                ->latest('id')
                ->paginate(8);
        }

        return view('user.projects-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'projects' => $projects,
        ]);
    }
public function userProjectShow(Request $request, ClientProject $project): View
    {
        $this->ensureUserCanAccessProject($request, $project);

        $user = $request->user();
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));

        $project->load([
            'client:id,user_id,name,email,phone,company,status',
            'quoteBuild:id,quote_id,status,estimated_total,currency,submitted_at',
            'media' => function ($query): void {
                $query->latest('id');
            },
            'invoices' => function ($query): void {
                $query->latest('id')->with('client:id,name,email');
            },
            'messages' => function ($query): void {
                $query->latest('id')->with('sender:id,name,email');
            },
            'comments' => function ($query): void {
                $query->latest('id')->with(['user:id,name,email,role', 'parent.user:id,name,email,role']);
            },
            'assignments.user:id,name,email,role',
            'serviceRequests' => function ($query): void {
                $query->latest('id')->with('requester:id,name,email');
            },
            'bookingRequests' => function ($query): void {
                $query->latest('id')->with('requester:id,name,email');
            },
        ]);

        $canViewBilling = $this->userMatchesClient($user, $project->client);
        $galleryPayload = $this->buildProjectGalleryPayload($project, true, false, true);

        return view('user.project-show', [
            'client' => $client,
            'portalStats' => $portalStats,
            'project' => $project,
            'galleryPayload' => $galleryPayload,
            'canViewBilling' => $canViewBilling,
            'isPaid' => $this->projectIsPaid($project),
        ]);
    }
public function userProjectCommentStore(Request $request, ClientProject $project): RedirectResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'parent_comment_id' => ['nullable', 'integer', 'exists:client_project_comments,id'],
        ]);

        $parentCommentId = $validated['parent_comment_id'] ?? null;
        if ($parentCommentId) {
            $parentComment = ClientProjectComment::query()
                ->where('id', $parentCommentId)
                ->where('client_project_id', $project->id)
                ->first();
            if (!$parentComment) {
                abort(404);
            }
        }

        $comment = ClientProjectComment::query()->create([
            'client_project_id' => $project->id,
            'parent_comment_id' => $parentCommentId,
            'user_id' => $request->user()?->id,
            'sender_role' => 'client',
            'body' => $validated['body'],
        ]);

        $internalActionUrl = $this->projectInternalActionUrl($project);
        $commentAuthor = (string) ($request->user()?->name ?? 'Client');
        $commentPreview = mb_strimwidth($validated['body'], 0, 140, '...');
        $this->notifyProjectAssignees(
            $project,
            'project_comment_added_internal',
            'Client comment added',
            "{$commentAuthor}: {$commentPreview}",
            $internalActionUrl,
            ['project_id' => $project->id, 'comment_id' => $comment->id],
            null,
            true
        );

        return back()->with('status', 'Project comment posted.');
    }

    public function userProjectCommentDestroy(Request $request, ClientProject $project, ClientProjectComment $comment): RedirectResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        if ((int) $comment->client_project_id !== (int) $project->id) {
            abort(404);
        }

        if ((int) $comment->user_id !== (int) ($request->user()?->id ?? 0)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('status', 'Project comment deleted.');
    }

    public function userProjectCommentUpdate(Request $request, ClientProject $project, ClientProjectComment $comment): RedirectResponse
    {
        $this->ensureUserCanAccessProject($request, $project);

        if ((int) $comment->client_project_id !== (int) $project->id) {
            abort(404);
        }

        if ((int) $comment->user_id !== (int) ($request->user()?->id ?? 0)) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
            'edited_at' => now(),
        ]);

        return back()->with('status', 'Project comment updated.');
    }
    public function userInvoicesIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));
        $invoices = $client
            ? ClientInvoice::query()
                ->where('client_id', $client->id)
                ->with([
                    'project:id,title,service_type,property_address,status',
                    'client:id,name,email',
                ])
                ->latest('id')
                ->paginate(10)
            : $this->emptyPaginator(10);

        return view('user.invoices-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'invoices' => $invoices,
        ]);
    }

    public function userQuotesIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));
        $quotes = $this->userQuoteQuery($user)
            ->latest('id')
            ->paginate(10, ['id', 'quote_id', 'status', 'estimated_total', 'currency', 'submitted_at', 'services', 'listing_type']);

        return view('user.quotes-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'quotes' => $quotes,
        ]);
    }

    public function userMessagesIndex(Request $request): View
    {
        $user = $request->user();
        $adminUsers = User::query()
            ->whereIn('role', $this->adminRoles())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
        $adminIds = $adminUsers->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $activeAdminId = $request->filled('admin_id') ? (int) $request->input('admin_id') : null;
        if ($activeAdminId === null || $activeAdminId <= 0) {
            $activeAdminId = (int) ($adminUsers->first()?->id ?: 0);
        }
        $activeAdmin = $activeAdminId > 0 ? $adminUsers->firstWhere('id', $activeAdminId) : null;
        $adminThreadMessages = UserMessage::query()
            ->with([
                'sender:id,name,email,role',
                'recipient:id,name,email,role',
            ])
            ->where(function ($query) use ($user): void {
                $query->where('sender_user_id', $user?->id)
                    ->orWhere('recipient_user_id', $user?->id);
            })
            ->latest('sent_at')
            ->latest('id')
            ->get();
        $adminThreadSummaries = collect(array_values($adminThreadMessages->reduce(function (array $carry, UserMessage $message) use ($user, $adminIds): array {
            $otherId = (int) ($message->sender_user_id === (int) ($user?->id ?? 0) ? $message->recipient_user_id : $message->sender_user_id);
            if (!in_array($otherId, $adminIds, true)) {
                return $carry;
            }
            if (!isset($carry[$otherId])) {
                $message->thread_admin_id = $otherId;
                $carry[$otherId] = $message;
            }
            return $carry;
        }, [])));
        $adminMessages = collect();
        if ($activeAdmin) {
            $adminMessages = UserMessage::query()
                ->with([
                    'sender:id,name,email,role',
                    'recipient:id,name,email,role',
                ])
                ->where(function ($query) use ($user, $activeAdmin): void {
                    $query->where('sender_user_id', $user?->id)
                        ->where('recipient_user_id', $activeAdmin->id);
                })
                ->orWhere(function ($query) use ($user, $activeAdmin): void {
                    $query->where('sender_user_id', $activeAdmin->id)
                        ->where('recipient_user_id', $user?->id);
                })
                ->latest('sent_at')
                ->latest('id')
                ->limit(200)
                ->get()
                ->sortBy(function (UserMessage $message): array {
                    return [
                        optional($message->sent_at)->timestamp ?? optional($message->created_at)->timestamp ?? 0,
                        $message->id,
                    ];
                })
                ->values();
        }
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));
        $messages = $client
            ? ClientMessage::query()
                ->where('client_id', $client->id)
                ->with([
                    'sender:id,name,email',
                    'project:id,title,status',
                ])
                ->latest('id')
                ->paginate(12)
            : $this->emptyPaginator(12);
        $serviceRequests = $client
            ? ClientServiceRequest::query()
                ->where('client_id', $client->id)
                ->with('project:id,title,status')
                ->latest('id')
                ->paginate(12, ['*'], 'requests_page')
            : $this->emptyPaginator(12, 'requests_page');
        $bookingRequests = $client
            ? BookingRequest::query()
                ->where('client_id', $client->id)
                ->with('project:id,title,status')
                ->latest('id')
                ->paginate(12, ['*'], 'booking_page')
            : $this->emptyPaginator(12, 'booking_page');
        $projects = $client
            ? ClientProject::query()
                ->where('client_id', $client->id)
                ->withCount([
                    'messages',
                    'serviceRequests',
                    'bookingRequests',
                ])
                ->latest('scheduled_at')
                ->latest('id')
                ->get(['id', 'title'])
            : collect();

        return view('user.messages-index', [
            'currentUser' => $user,
            'client' => $client,
            'portalStats' => $portalStats,
            'messages' => $messages,
            'serviceRequests' => $serviceRequests,
            'bookingRequests' => $bookingRequests,
            'projects' => $projects,
            'adminUsers' => $adminUsers,
            'adminThreadSummaries' => $adminThreadSummaries,
            'activeAdmin' => $activeAdmin,
            'adminMessages' => $adminMessages,
        ]);
    }

    public function userAdminMessageStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $admin = User::query()
            ->whereIn('role', $this->adminRoles())
            ->where('id', (int) $validated['admin_user_id'])
            ->first();

        if (!$admin) {
            return back()->withErrors([
                'admin_user_id' => 'Selected admin is not available.',
            ])->withInput();
        }

        UserMessage::create([
            'sender_user_id' => $request->user()?->id,
            'recipient_user_id' => $admin->id,
            'message' => $validated['message'],
            'sent_at' => now(),
        ]);

        $senderName = (string) ($request->user()?->name ?? 'Client');
        $messagePreview = mb_strimwidth($validated['message'], 0, 140, '...');
        $this->notificationService()->notifyUser(
            $admin->id,
            'direct_message_received',
            "New message from {$senderName}",
            $messagePreview,
            route('admin.messages.index', ['mode' => 'users', 'user_id' => (int) ($request->user()?->id ?? 0)]),
            ['sender_id' => (int) ($request->user()?->id ?? 0)]
        );

        return redirect()->route('user.messages.index', [
            'admin_id' => $admin->id,
        ])->with('status', 'Message sent successfully.');
    }

        public function userServiceRequestsIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user, ['projects:id,client_id,title']);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));

        $projects = $client?->projects?->sortBy('title')->values() ?? collect();
        $serviceRequests = $client
            ? ClientServiceRequest::query()
                ->with(['project:id,title'])
                ->where('client_id', $client->id)
                ->latest('id')
                ->paginate(20)
            : $this->emptyPaginator(20, 'service_page');

        $editRequest = null;
        if ($client && $request->filled('edit')) {
            $editRequest = ClientServiceRequest::query()
                ->where('client_id', $client->id)
                ->find((int) $request->input('edit'));
        }

        return view('user.service-requests-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'projects' => $projects,
            'serviceRequests' => $serviceRequests,
            'editRequest' => $editRequest,
        ]);
    }

    public function userBookingRequestsIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user, ['projects:id,client_id,title']);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));

        $projects = $client?->projects?->sortBy('title')->values() ?? collect();
        $bookingRequests = $client
            ? BookingRequest::query()
                ->with(['project:id,title'])
                ->where('client_id', $client->id)
                ->latest('id')
                ->paginate(20)
            : $this->emptyPaginator(20, 'booking_page');

        $editRequest = null;
        if ($client && $request->filled('edit')) {
            $editRequest = BookingRequest::query()
                ->where('client_id', $client->id)
                ->find((int) $request->input('edit'));
        }

        return view('user.booking-requests-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'projects' => $projects,
            'bookingRequests' => $bookingRequests,
            'editRequest' => $editRequest,
        ]);
    }
public function userDeliveriesIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));
        $projects = $client
            ? ClientProject::query()
                ->where('client_id', $client->id)
                ->withCount([
                    'media as gallery_media_count' => function ($mediaQuery): void {
                        $mediaQuery->whereIn('type', ['image', 'video']);
                    },
                    'media as final_zip_count' => function ($mediaQuery): void {
                        $mediaQuery->where('type', 'final_zip');
                    },
                ])
                ->with([
                    'media' => function ($mediaQuery): void {
                        $mediaQuery->latest('id')->with('uploader:id,name,email,role');
                    },
                    'invoices:id,client_project_id,status',
                ])
                ->latest('scheduled_at')
                ->latest('id')
                ->paginate(8)
            : $this->emptyPaginator(8);
        $galleryPayloadByProject = $client
            ? $this->buildProjectGalleryPayloadMap($projects->getCollection(), true, false, true)
            : [];

        return view('user.deliveries-index', [
            'client' => $client,
            'portalStats' => $portalStats,
            'projects' => $projects,
            'galleryPayloadByProject' => $galleryPayloadByProject,
        ]);
    }

    public function userAccountIndex(Request $request): View
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user, [
            'projects' => function ($query): void {
                $query->latest('id')->limit(3);
            },
        ]);
        $portalStats = $this->buildUserPortalStats($client, $this->userLeadQuery($user), $this->userQuoteQuery($user));

        return view('user.account-index', [
            'client' => $client,
            'portalStats' => $portalStats,
        ]);
    }

    public function userAccountUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $client = $this->resolvePortalClient($user);

        $validated = $request->validate([
            'notify_portal' => ['nullable', 'in:0,1'],
            'notify_invoice_email' => ['nullable', 'in:0,1'],
        ]);

        if (!$client) {
            return back()->withErrors(['account' => 'Client record not found. Please contact support.']);
        }

        $client->notify_portal = (bool) ($validated['notify_portal'] ?? false);
        $client->notify_invoice_email = (bool) ($validated['notify_invoice_email'] ?? false);
        $client->save();

        return back()->with('status', 'Notification preferences updated.');
    }

    public function userInvoicePay(Request $request, ClientInvoice $invoice): View
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);
        $invoice->loadMissing(['client', 'project']);

        $settings = $this->resolveInvoiceSettings();
        $demoMode = $this->isPaymentDemoMode();

        return view('user.invoice-pay', [
            'invoice' => $invoice,
            'settings' => $settings,
            'stripeEnabled' => (bool) ($settings->stripe_enabled ?? false),
            'paypalEnabled' => (bool) ($settings->paypal_enabled ?? false),
            'manualEnabled' => (bool) ($settings->manual_enabled ?? true),
            'manualInstructions' => (string) ($settings->manual_instructions ?? ''),
            'stripePublicKey' => $this->resolveStripePublishableKey(),
            'paypalClientId' => $this->resolvePayPalClientId(),
            'demoMode' => $demoMode,
        ]);
    }

    public function userInvoiceStripeCheckout(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('status', 'Invoice already paid.');
        }

        $invoice->refresh();
        $balanceDue = (float) ($invoice->balance_due ?? $invoice->amount);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        $amount = (float) $validated['amount'];
        if ($amount > $balanceDue) {
            return back()->withErrors(['payment' => 'Payment amount cannot exceed the balance due.']);
        }

        $settings = $this->resolveInvoiceSettings();
        if (!($settings->stripe_enabled ?? false)) {
            return back()->withErrors(['payment' => 'Stripe payments are disabled.']);
        }

        $demoMode = $this->isPaymentDemoMode();
        if ($demoMode || blank(env('STRIPE_SECRET_KEY'))) {
            $this->recordInvoicePayment($invoice, $amount, $request->user(), 'stripe_demo', 'demo-' . now()->timestamp, 'card', [
                'mode' => 'demo',
            ], 'Client partial payment');

            return redirect()->route('user.invoices.index')->with('status', 'Demo payment recorded.');
        }

        $invoice->loadMissing(['client', 'project']);
        $currency = strtoupper((string) ($invoice->currency ?: 'USD'));

        $successUrl = route('user.invoices.stripe.success', [$invoice]) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('user.invoices.pay', [$invoice]);

        $response = Http::asForm()->withBasicAuth($this->resolveStripeSecretKey(), '')
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $invoice->id,
                'customer_email' => $invoice->client?->email,
                'payment_method_types[]' => 'card',
                'line_items[0][price_data][currency]' => strtolower($currency),
                'line_items[0][price_data][product_data][name]' => 'Invoice ' . $invoice->invoice_number,
                'line_items[0][price_data][unit_amount]' => (int) round($amount * 100),
                'line_items[0][quantity]' => 1,
                'metadata[invoice_id]' => (string) $invoice->id,
                'metadata[amount_requested]' => number_format($amount, 2, '.', ''),
            ]);

        if (!$response->successful()) {
            return back()->withErrors(['payment' => 'Stripe payment could not be started.']);
        }

        $payload = $response->json();
        if (empty($payload['url'])) {
            return back()->withErrors(['payment' => 'Stripe did not return a checkout URL.']);
        }

        return redirect()->away((string) $payload['url']);
    }

    public function userInvoiceStripeSuccess(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);

        if ($invoice->status === 'paid') {
            return redirect()->route('user.invoices.index')->with('status', 'Invoice already paid.');
        }

        $sessionId = (string) $request->string('session_id');
        if ($sessionId === '') {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Missing Stripe session.']);
        }

        $demoMode = $this->isPaymentDemoMode();
        if ($demoMode || blank(env('STRIPE_SECRET_KEY'))) {
            $this->recordInvoicePayment($invoice, (float) ($invoice->balance_due ?? $invoice->amount), $request->user(), 'stripe_demo', $sessionId, 'card', [
                'mode' => 'demo',
            ], 'Stripe demo payment');

            return redirect()->route('user.invoices.index')->with('status', 'Demo payment recorded.');
        }

        $response = Http::withBasicAuth($this->resolveStripeSecretKey(), '')
            ->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);

        if (!$response->successful()) {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Unable to verify Stripe payment.']);
        }

        $session = $response->json();
        if (($session['payment_status'] ?? '') !== 'paid') {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Stripe payment not completed yet.']);
        }

        $amountTotal = (float) (($session['amount_total'] ?? 0) / 100);
        if ($amountTotal <= 0) {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Stripe payment amount could not be verified.']);
        }

        $this->recordInvoicePayment($invoice, $amountTotal, $request->user(), 'stripe', $sessionId, 'card', [
            'payment_status' => $session['payment_status'] ?? null,
            'amount_total' => $session['amount_total'] ?? null,
            'currency' => $session['currency'] ?? null,
        ], 'Stripe payment');

        return redirect()->route('user.invoices.index')->with('status', 'Payment completed successfully.');
    }

    public function userInvoicePayPalCreate(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('status', 'Invoice already paid.');
        }

        $invoice->refresh();
        $balanceDue = (float) ($invoice->balance_due ?? $invoice->amount);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        $amountValue = (float) $validated['amount'];
        if ($amountValue > $balanceDue) {
            return back()->withErrors(['payment' => 'Payment amount cannot exceed the balance due.']);
        }

        $settings = $this->resolveInvoiceSettings();
        if (!($settings->paypal_enabled ?? false)) {
            return back()->withErrors(['payment' => 'PayPal payments are disabled.']);
        }

        $demoMode = $this->isPaymentDemoMode();
        if ($demoMode || blank(env('PAYPAL_CLIENT_ID')) || blank(env('PAYPAL_SECRET'))) {
            $this->recordInvoicePayment($invoice, $amountValue, $request->user(), 'paypal_demo', 'demo-' . now()->timestamp, 'paypal', [
                'mode' => 'demo',
            ], 'PayPal demo payment');

            return redirect()->route('user.invoices.index')->with('status', 'Demo payment recorded.');
        }

        $baseUrl = $this->paypalBaseUrl();
        $tokenResponse = Http::asForm()->withBasicAuth($this->resolvePayPalClientId(), $this->resolvePayPalSecret())
            ->post($baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            return back()->withErrors(['payment' => 'PayPal authentication failed.']);
        }

        $accessToken = (string) ($tokenResponse->json()['access_token'] ?? '');
        if ($accessToken === '') {
            return back()->withErrors(['payment' => 'PayPal token missing.']);
        }

        $invoice->loadMissing(['client', 'project']);
        $amount = number_format($amountValue, 2, '.', '');
        $currency = strtoupper((string) ($invoice->currency ?: 'USD'));

        $orderResponse = Http::withToken($accessToken)
            ->post($baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $invoice->id,
                    'description' => 'Invoice ' . $invoice->invoice_number,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('user.invoices.paypal.success', $invoice),
                    'cancel_url' => route('user.invoices.pay', $invoice),
                    'brand_name' => 'Maccento CRM',
                ],
            ]);

        if (!$orderResponse->successful()) {
            return back()->withErrors(['payment' => 'Unable to create PayPal order.']);
        }

        $order = $orderResponse->json();
        $approvalUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;
        if (!is_string($approvalUrl) || $approvalUrl === '') {
            return back()->withErrors(['payment' => 'PayPal approval link missing.']);
        }

        return redirect()->away($approvalUrl);
    }

    public function userInvoicePayPalSuccess(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);

        if ($invoice->status === 'paid') {
            return redirect()->route('user.invoices.index')->with('status', 'Invoice already paid.');
        }

        $orderId = (string) $request->string('token');
        if ($orderId === '') {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Missing PayPal order.']);
        }

        $demoMode = $this->isPaymentDemoMode();
        if ($demoMode || blank(env('PAYPAL_CLIENT_ID')) || blank(env('PAYPAL_SECRET'))) {
            $this->recordInvoicePayment($invoice, (float) ($invoice->balance_due ?? $invoice->amount), $request->user(), 'paypal_demo', $orderId, 'paypal', [
                'mode' => 'demo',
            ], 'PayPal demo payment');

            return redirect()->route('user.invoices.index')->with('status', 'Demo payment recorded.');
        }

        $baseUrl = $this->paypalBaseUrl();
        $tokenResponse = Http::asForm()->withBasicAuth($this->resolvePayPalClientId(), $this->resolvePayPalSecret())
            ->post($baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'PayPal authentication failed.']);
        }

        $accessToken = (string) ($tokenResponse->json()['access_token'] ?? '');
        if ($accessToken === '') {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'PayPal token missing.']);
        }

        $captureResponse = Http::withToken($accessToken)
            ->post($baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');

        if (!$captureResponse->successful()) {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'Unable to capture PayPal payment.']);
        }

        $capture = $captureResponse->json();
        if (($capture['status'] ?? '') !== 'COMPLETED') {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'PayPal payment not completed yet.']);
        }

        $capturedAmount = 0.0;
        foreach (($capture['purchase_units'] ?? []) as $unit) {
            foreach (($unit['payments']['captures'] ?? []) as $captureItem) {
                $capturedAmount += (float) ($captureItem['amount']['value'] ?? 0);
            }
        }
        if ($capturedAmount <= 0) {
            return redirect()->route('user.invoices.pay', $invoice)->withErrors(['payment' => 'PayPal payment amount could not be verified.']);
        }

        $this->recordInvoicePayment($invoice, $capturedAmount, $request->user(), 'paypal', $orderId, 'paypal', [
            'status' => $capture['status'] ?? null,
            'amount' => $capturedAmount,
        ], 'PayPal payment');

        return redirect()->route('user.invoices.index')->with('status', 'Payment completed successfully.');
    }

    public function userInvoiceManualNotify(Request $request, ClientInvoice $invoice): RedirectResponse
    {
        $this->ensureUserCanAccessInvoice($request, $invoice);

        $invoice->refresh();
        $balanceDue = (float) ($invoice->balance_due ?? $invoice->amount);
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);
        $amount = isset($validated['amount']) ? (float) $validated['amount'] : $balanceDue;
        if ($amount > $balanceDue) {
            return back()->withErrors(['payment' => 'Payment amount cannot exceed the balance due.']);
        }

        $invoice->loadMissing(['client', 'project']);
        $client = $invoice->client;
        if ($client) {
            ClientMessage::create([
                'client_id' => $client->id,
                'client_project_id' => $invoice->client_project_id,
                'sender_user_id' => $request->user()?->id,
                'sender_role' => 'client',
                'message' => 'Client will pay invoice ' . $invoice->invoice_number . ' manually. Amount: ' . number_format($amount, 2) . ' ' . strtoupper((string) $invoice->currency) . '.',
                'sent_at' => now(),
            ]);
        }

        $this->notificationService()->notifyInternal(
            'invoice_manual_payment',
            'Manual payment noted',
            'Client will pay invoice ' . $invoice->invoice_number . ' manually. Amount: ' . number_format($amount, 2) . ' ' . strtoupper((string) $invoice->currency) . '.',
            route('admin.invoices.index'),
            ['invoice_id' => $invoice->id]
        );

        return back()->with('status', 'Manual payment request sent to admin.');
    }
    public function userInvoicePdfDownload(Request $request, ClientInvoice $invoice)
    {
        $invoice->loadMissing([
            'client:id,user_id,name,email,phone,company',
            'project:id,title,service_type,property_address',
        ]);

        $this->ensureUserCanAccessInvoice($request, $invoice);

        $clientName = trim((string) ($invoice->client?->name ?? 'client'));
        $safeName = Str::slug($clientName !== '' ? $clientName : 'client');
        $filename = 'invoice-' . $invoice->invoice_number . '-' . $safeName . '.pdf';

        $settings = $this->resolveInvoiceSettings();
        $subtotal = round((float) $invoice->amount, 2);
        $includeTax = (bool) $settings->include_tax_on_pdf;
        $taxRate = max(0.0, (float) $settings->tax_rate_percent);
        $taxAmount = $includeTax ? round(($subtotal * $taxRate) / 100, 2) : 0.0;
        $total = round($subtotal + $taxAmount, 2);

        $pdf = Pdf::loadView('user.pdf.invoice', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'project' => $invoice->project,
            'subtotal' => $subtotal,
            'includeTax' => $includeTax,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'total' => $total,
        ]);

        return $pdf->download($filename);
    }
    public function userServiceRequestDestroy(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $client = $this->resolvePortalClient($request->user());
        if (!$client || (int) $serviceRequest->client_id !== (int) $client->id) {
            abort(403);
        }

        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [
            'action' => 'delete',
            'snapshot' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date', 'status']),
        ]);

        $serviceRequest->delete();

        return back()->with('status', 'Service request deleted.');
    }

    public function userBookingRequestDestroy(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $client = $this->resolvePortalClient($request->user());
        if (!$client || (int) $bookingRequest->client_id !== (int) $client->id) {
            abort(403);
        }

        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [
            'action' => 'delete',
            'snapshot' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes', 'status']),
        ]);

        $bookingRequest->delete();

        return back()->with('status', 'Booking request deleted.');
    }
    public function userServiceRequestUpdate(Request $request, ClientServiceRequest $serviceRequest): RedirectResponse
    {
        $client = $this->resolvePortalClient($request->user());
        if (!$client || (int) $serviceRequest->client_id !== (int) $client->id) {
            abort(403);
        }

        $validated = $request->validate([
            'client_project_id' => ['required', 'integer'],
            'requested_service' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        $before = $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']);

        $serviceRequest->client_project_id = $validated['client_project_id'];
        $serviceRequest->requested_service = $validated['requested_service'];
        $serviceRequest->subject = $validated['subject'] ?? null;
        $serviceRequest->details = $validated['details'] ?? null;
        $serviceRequest->preferred_date = $validated['preferred_date'] ?? null;
        $serviceRequest->save();

        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [
            'action' => 'update',
            'before' => $before,
            'after' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']),
        ]);

        return back()->with('status', 'Service request updated.');
    }

    public function userBookingRequestUpdate(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $client = $this->resolvePortalClient($request->user());
        if (!$client || (int) $bookingRequest->client_id !== (int) $client->id) {
            abort(403);
        }

        $validated = $request->validate([
            'requested_service' => ['required', 'string', 'max:160'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time_window' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $bookingRequest->only(['requested_service', 'preferred_date', 'preferred_time_window', 'notes']);

        $bookingRequest->requested_service = $validated['requested_service'];
        $bookingRequest->preferred_date = $validated['preferred_date'] ?? null;
        $bookingRequest->preferred_time_window = $validated['preferred_time_window'] ?? null;
        $bookingRequest->notes = $validated['notes'] ?? null;
        $bookingRequest->save();

        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [
            'action' => 'update',
            'before' => $before,
            'after' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes']),
        ]);

        return back()->with('status', 'Booking request updated.');
    }
    public function userBookingRequestStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'client_project_id' => ['nullable', 'integer'],
            'requested_service' => ['required', 'string', 'max:160'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time_window' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->createBookingRequest($user, $validated);

        return back()->with('status', 'Booking request submitted successfully.');
    }

    public function userServiceRequestStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'client_project_id' => ['required', 'integer'],
            'requested_service' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);
        $this->createServiceRequest($user, $validated);

        return back()->with('status', 'Service request submitted successfully.');
    }

    public function userUnifiedRequestStore(Request $request): RedirectResponse
    {
        $type = strtolower(trim((string) $request->input('request_type', 'service')));

        if ($type === 'booking') {
            $validated = $request->validate([
                'client_project_id' => ['nullable', 'integer'],
                'requested_service' => ['required', 'string', 'max:160'],
                'preferred_date' => ['nullable', 'date'],
                'preferred_time_window' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $this->createBookingRequest($request->user(), $validated);

            return back()->with('status', 'Booking request submitted successfully.');
        }

        $validated = $request->validate([
            'client_project_id' => ['required', 'integer'],
            'requested_service' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        $this->createServiceRequest($request->user(), $validated);

        return back()->with('status', 'Service request submitted successfully.');
    }

    private function createServiceRequest(User $user, array $validated): void
    {
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

        $projectId = null;
        $projectTitle = null;
        if (!blank($validated['client_project_id'] ?? null)) {
            $project = ClientProject::query()
                ->where('client_id', $client->id)
                ->where('id', (int) $validated['client_project_id'])
                ->first(['id', 'title']);
            if ($project) {
                $projectId = (int) $project->id;
                $projectTitle = (string) $project->title;
            }
        }

        $serviceRequest = ClientServiceRequest::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'requester_user_id' => $user->id,
            'requested_service' => $validated['requested_service'],
            'subject' => $validated['subject'] ?? null,
            'details' => $validated['details'] ?? null,
            'preferred_date' => $validated['preferred_date'] ?? null,
            'status' => 'new',
        ]);

        $this->logRequestEdit('service', (int) $serviceRequest->id, $client->id, $user, [
            'action' => 'create',
            'snapshot' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date', 'status']),
        ]);

        $messagePrefix = 'New service request submitted: ';
        if ($projectTitle !== null && $projectTitle !== '') {
            $messagePrefix = 'Additional service request for project "' . $projectTitle . '": ';
        }

        ClientMessage::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'sender_user_id' => $user->id,
            'sender_role' => 'client',
            'message' => $messagePrefix . $validated['requested_service'] . ($validated['subject'] ? ' - ' . $validated['subject'] : ''),
            'sent_at' => now(),
        ]);

        $internalSummary = "{$user->name} submitted: {$validated['requested_service']}.";
        if ($projectTitle !== null && $projectTitle !== '') {
            $internalSummary = "{$user->name} requested additional service for {$projectTitle}: {$validated['requested_service']}";
        }

        $this->notificationService()->notifyInternal(
            'new_service_request',
            'New client service request',
            $internalSummary,
            route('admin.clients.index')
        );
    }

    private function createBookingRequest(User $user, array $validated): void
    {
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
                'notes' => 'Auto-created from client portal booking request.',
            ]);
        }

        $projectId = null;
        $projectTitle = null;
        $leadProfileId = null;
        if (!blank($validated['client_project_id'] ?? null)) {
            $project = ClientProject::query()
                ->where('client_id', $client->id)
                ->where('id', (int) $validated['client_project_id'])
                ->first(['id', 'title', 'lead_profile_id']);
            if ($project) {
                $projectId = (int) $project->id;
                $projectTitle = (string) $project->title;
                $leadProfileId = $project->lead_profile_id ? (int) $project->lead_profile_id : null;
            }
        }

        $bookingRequest = BookingRequest::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'lead_profile_id' => $leadProfileId,
            'requester_user_id' => $user->id,
            'requested_service' => $validated['requested_service'],
            'preferred_date' => $validated['preferred_date'] ?? null,
            'preferred_time_window' => $validated['preferred_time_window'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'new',
        ]);

        $this->logRequestEdit('booking', (int) $bookingRequest->id, $client->id, $user, [
            'action' => 'create',
            'snapshot' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes', 'status']),
        ]);

        $preferredBits = [];
        if (!blank($validated['preferred_date'] ?? null)) {
            $preferredBits[] = Carbon::parse((string) $validated['preferred_date'])->format('Y-m-d');
        }
        if (!blank($validated['preferred_time_window'] ?? null)) {
            $preferredBits[] = (string) $validated['preferred_time_window'];
        }

        $messagePrefix = 'New booking request submitted: ';
        if ($projectTitle !== null && $projectTitle !== '') {
            $messagePrefix = 'Booking request for project "' . $projectTitle . '": ';
        }

        $message = $messagePrefix . $validated['requested_service'];
        if ($preferredBits !== []) {
            $message .= ' (Preferred: ' . implode(' - ', $preferredBits) . ')';
        }

        ClientMessage::create([
            'client_id' => $client->id,
            'client_project_id' => $projectId,
            'sender_user_id' => $user->id,
            'sender_role' => 'client',
            'message' => $message,
            'sent_at' => now(),
        ]);

        $internalSummary = "{$user->name} requested booking for {$validated['requested_service']}.";
        if ($projectTitle !== null && $projectTitle !== '') {
            $internalSummary = "{$user->name} submitted a booking request for {$projectTitle}: {$validated['requested_service']}.";
        }

        $this->notificationService()->notifyInternal(
            'new_booking_request',
            'New booking request',
            $internalSummary,
            route('admin.booking-requests.index'),
            ['booking_request_id' => $bookingRequest->id]
        );
    }

    public function adminQuoteShow(QuoteBuild $quote): View
    {
        $quote->load([
            'leadProfile:id,name,email,phone,service_type,property_type,status',
            'events.creator:id,name,email',
        ]);

        return view('admin.quote-show', [
            'quote' => $quote,
            'currencyOptions' => $this->currencyOptions(),
            'defaultCurrency' => $this->resolveDefaultCurrency(),
        ]);
    }

    public function adminQuoteStatusUpdate(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'in:new,reviewed,contacted,booked,lost'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $quote->status;
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

        $this->logActivity(
            $request,
            'quote',
            $quote->id,
            $quote->client_id ?? null,
            $request->user(),
            'status_update',
            'Quote status updated: ' . $quote->quote_id,
            [
                'before' => ['status' => $previousStatus],
                'after' => ['status' => $quote->status],
            ]
        );

        app(OutboundWebhookService::class)->send('quote.status_updated', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_id,
            'status' => $quote->status,
            'previous_status' => $previousStatus,
            'contact_email' => data_get($quote->options, 'contact_email'),
        ]);

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

        $this->logActivity(
            $request,
            'quote',
            $quote->id,
            $quote->client_id ?? null,
            $request->user(),
            'send',
            'Quote email resent: ' . $quote->quote_id
        );

        return back()->with('status', 'Quote emails resent.');
    }

    public function adminQuoteDestroy(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $quoteId = $quote->quote_id;
        $snapshot = $quote->only(['quote_id', 'status', 'total', 'currency']);
        $quote->delete();

        $this->logActivity(
            $request,
            'quote',
            $quote->id,
            $quote->client_id ?? null,
            $request->user(),
            'delete',
            'Quote deleted: ' . $quoteId,
            ['before' => $snapshot]
        );

        return redirect()->route('admin.quotes.index')->with('status', "Quote {$quoteId} deleted successfully. Client can submit a new request.");
    }

    public function adminQuoteLineItemsUpdate(Request $request, QuoteBuild $quote): RedirectResponse
    {
        $this->ensurePipelineWriteAccess($request);

        $packageCode = strtolower((string) data_get($quote->options, 'package_code', ''));
        if (in_array($packageCode, ['essential', 'signature', 'prestige'], true)) {
            return back()->withErrors(['line_items' => 'Fixed package quotes are locked for editing.']);
        }

        $allowedCurrencies = $this->resolveAllowedCurrencyCodes();
        $currencyRule = ['nullable', 'string', 'max:8'];
        if ($allowedCurrencies !== []) {
            $currencyRule[] = Rule::in($allowedCurrencies);
        }

        $validated = $request->validate([
            'currency' => $currencyRule,
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

    private function projectInternalActionUrl(ClientProject $project): string
    {
        return route('admin.media-delivery.index', ['media_search' => $project->title]) . '#project-' . $project->id;
    }

    private function notifyProjectAssignees(ClientProject $project, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = [], ?int $actorId = null, bool $includeManagers = true): void
    {
        $project->loadMissing('assignments');
        $assigneeIds = $project->assignments
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $recipientIds = $assigneeIds;
        if ($includeManagers) {
            $managerIds = User::query()
                ->whereIn('role', ['owner', 'admin', 'manager'])
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $recipientIds = array_merge($recipientIds, $managerIds);
        }

        $recipientIds = array_values(array_unique(array_filter($recipientIds, static fn ($id): bool => (int) $id > 0)));
        if ($actorId) {
            $recipientIds = array_values(array_filter($recipientIds, static fn ($id): bool => (int) $id !== (int) $actorId));
        }

        foreach ($recipientIds as $userId) {
            $this->notificationService()->notifyUser((int) $userId, $type, $title, $body, $actionUrl, $data);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function notifyClientUser(Client $client, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        if ($this->tableHasColumn('clients', 'notify_portal') && $client->notify_portal === false) {
            return;
        }

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

    private function leadAutoEmailSources(): array
    {
        return [
            'website_packages' => [
                'label' => 'Website Packages',
                'description' => 'Leads captured from the package builder checkout/contact steps.',
            ],
            'website_contact_form_submission' => [
                'label' => 'Website Contact Form',
                'description' => 'Leads captured from the public website contact form.',
            ],
            'ai_chat_lead' => [
                'label' => 'AI Chat',
                'description' => 'Leads captured from AI chat once an email is provided.',
            ],
        ];
    }

    private function renderAdminEmailMailbox(Request $request, string $folder): View
    {
        $defaultRecipient = (string) env('QUOTE_ADMIN_EMAIL', (string) config('mail.lead_alert_address', (string) config('mail.from.address')));
        $defaultReplyTo = $this->crmInboundReplyToAddress();

        $quickTemplates = [
            [
                'key' => 'delivery_test',
                'title' => 'Delivery Test',
                'description' => 'Confirm SMTP delivery and inbox routing in one click.',
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

        $folderCounts = [
            'inbox' => InboundEmail::query()->count(),
            'sent' => EmailLog::query()->count(),
            'drafts' => EmailDraft::query()->where('status', 'draft')->count(),
        ];

        $mailboxItems = new LengthAwarePaginator([], 0, 20, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $eventTimeline = collect();
        $selectedTimeline = collect();
        $selectedMessage = null;
        $threadMessages = collect();

        if ($folder === 'inbox') {
            $mailboxItems = InboundEmail::query()
                ->with(['client:id,name,email', 'project:id,title'])
                ->latest('id')
                ->paginate(20)
                ->withQueryString();
        } elseif ($folder === 'drafts') {
            $mailboxItems = EmailDraft::query()
                ->with(['creator:id,name,email', 'project:id,title'])
                ->where('status', 'draft')
                ->latest('id')
                ->paginate(20)
                ->withQueryString();
        } else {
            $mailboxItems = EmailLog::query()
                ->with('creator:id,name,email')
                ->latest('id')
                ->paginate(20)
                ->withQueryString();

            $emailLogIds = $mailboxItems->getCollection()->pluck('id')->values()->all();
            if (count($emailLogIds) > 0) {
                $eventTimeline = SendgridWebhookEvent::query()
                    ->whereIn('email_log_id', $emailLogIds)
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('email_log_id')
                    ->map(static fn ($items) => $items->take(8)->values());
            }
        }

        $openMessageId = $request->filled('open_id') ? max(0, (int) $request->query('open_id')) : 0;
        if ($folder === 'inbox' && $openMessageId > 0) {
            $selectedMessage = InboundEmail::query()
                ->with(['client:id,name,email', 'project:id,title'])
                ->find($openMessageId);

            if ($selectedMessage) {
                $threadQuery = InboundEmail::query();
                if (!blank($selectedMessage->client_id)) {
                    $threadQuery->where('client_id', (int) $selectedMessage->client_id);
                } else {
                    $threadQuery->whereRaw('LOWER(from_email) = ?', [Str::lower((string) $selectedMessage->from_email)]);
                }

                $threadMessages = $threadQuery
                    ->latest('received_at')
                    ->latest('id')
                    ->limit(16)
                    ->get()
                    ->map(static function (InboundEmail $mail) use ($openMessageId): array {
                        $body = trim((string) ($mail->body_text ?? ''));
                        if ($body === '') {
                            $body = trim(strip_tags((string) ($mail->body_html ?? '')));
                        }

                        return [
                            'id' => (int) $mail->id,
                            'kind' => 'inbound',
                            'direction' => 'inbound',
                            'subject' => (string) ($mail->subject ?? '(No subject)'),
                            'body' => $body,
                            'snippet' => Str::limit($body !== '' ? $body : '(No message body)', 200),
                            'from_label' => trim((string) ($mail->from_name ?: $mail->from_email)),
                            'from_email' => (string) ($mail->from_email ?? ''),
                            'to_email' => (string) ($mail->to_email ?? ''),
                            'status' => (string) ($mail->status ?? 'received'),
                            'display_at' => optional($mail->received_at ?? $mail->created_at)?->format('Y-m-d H:i') ?: '-',
                            'sort_at' => optional($mail->received_at ?? $mail->created_at)?->timestamp ?? 0,
                            'is_selected' => (int) $mail->id === (int) $openMessageId,
                        ];
                    })
                    ->sortByDesc('sort_at')
                    ->values();

                // Guard against malformed legacy records that cannot be matched into a thread.
                if ($threadMessages->isEmpty()) {
                    $body = trim((string) ($selectedMessage->body_text ?? ''));
                    if ($body === '') {
                        $body = trim(strip_tags((string) ($selectedMessage->body_html ?? '')));
                    }

                    $threadMessages = collect([[
                        'id' => (int) $selectedMessage->id,
                        'kind' => 'inbound',
                        'direction' => 'inbound',
                        'subject' => (string) ($selectedMessage->subject ?? '(No subject)'),
                        'body' => $body,
                        'snippet' => Str::limit($body !== '' ? $body : '(No message body)', 200),
                        'from_label' => trim((string) ($selectedMessage->from_name ?: $selectedMessage->from_email)),
                        'from_email' => (string) ($selectedMessage->from_email ?? ''),
                        'to_email' => (string) ($selectedMessage->to_email ?? ''),
                        'status' => (string) ($selectedMessage->status ?? 'received'),
                        'display_at' => optional($selectedMessage->received_at ?? $selectedMessage->created_at)?->format('Y-m-d H:i') ?: '-',
                        'sort_at' => optional($selectedMessage->received_at ?? $selectedMessage->created_at)?->timestamp ?? 0,
                        'is_selected' => true,
                    ]]);
                }
            }
        }

        if ($folder === 'sent' && $openMessageId > 0) {
            $selectedMessage = EmailLog::query()
                ->with('creator:id,name,email')
                ->find($openMessageId);

            if ($selectedMessage) {
                $selectedTimeline = SendgridWebhookEvent::query()
                    ->where('email_log_id', (int) $selectedMessage->id)
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get();

                $recipient = Str::lower(trim((string) ($selectedMessage->recipient_email ?? '')));
                $outboundMessages = EmailLog::query()
                    ->whereRaw('LOWER(recipient_email) = ?', [$recipient])
                    ->latest('sent_at')
                    ->latest('id')
                    ->limit(12)
                    ->get()
                    ->map(static function (EmailLog $mail) use ($openMessageId): array {
                        $body = trim((string) ($mail->body_preview ?? ''));

                        return [
                            'id' => (int) $mail->id,
                            'kind' => 'sent',
                            'direction' => 'outbound',
                            'subject' => (string) ($mail->subject ?? '(No subject)'),
                            'body' => $body,
                            'snippet' => Str::limit($body !== '' ? $body : '(No preview stored)', 200),
                            'from_label' => (string) ($mail->creator?->name ?? 'CRM user'),
                            'from_email' => (string) ($mail->reply_to ?: config('mail.from.address', '')),
                            'to_email' => (string) ($mail->recipient_email ?? ''),
                            'status' => (string) ($mail->status ?? 'sent'),
                            'display_at' => optional($mail->sent_at ?? $mail->created_at)?->format('Y-m-d H:i') ?: '-',
                            'sort_at' => optional($mail->sent_at ?? $mail->created_at)?->timestamp ?? 0,
                            'is_selected' => (int) $mail->id === (int) $openMessageId,
                        ];
                    });

                $inboundReplies = InboundEmail::query()
                    ->whereRaw('LOWER(from_email) = ?', [$recipient])
                    ->latest('received_at')
                    ->latest('id')
                    ->limit(12)
                    ->get()
                    ->map(static function (InboundEmail $mail): array {
                        $body = trim((string) ($mail->body_text ?? ''));
                        if ($body === '') {
                            $body = trim(strip_tags((string) ($mail->body_html ?? '')));
                        }

                        return [
                            'id' => (int) $mail->id,
                            'kind' => 'inbound',
                            'direction' => 'inbound',
                            'subject' => (string) ($mail->subject ?? '(No subject)'),
                            'body' => $body,
                            'snippet' => Str::limit($body !== '' ? $body : '(No message body)', 200),
                            'from_label' => trim((string) ($mail->from_name ?: $mail->from_email)),
                            'from_email' => (string) ($mail->from_email ?? ''),
                            'to_email' => (string) ($mail->to_email ?? ''),
                            'status' => (string) ($mail->status ?? 'received'),
                            'display_at' => optional($mail->received_at ?? $mail->created_at)?->format('Y-m-d H:i') ?: '-',
                            'sort_at' => optional($mail->received_at ?? $mail->created_at)?->timestamp ?? 0,
                            'is_selected' => false,
                        ];
                    });

                $threadMessages = $outboundMessages
                    ->concat($inboundReplies)
                    ->sortByDesc('sort_at')
                    ->take(24)
                    ->values();
            }
        }

        $draftId = $request->filled('draft') ? (int) $request->query('draft') : null;
        if ($folder === 'drafts' && $openMessageId > 0) {
            $draftId = $openMessageId;
        }
        $selectedDraft = null;
        if ($draftId !== null && $draftId > 0) {
            $selectedDraft = EmailDraft::query()->find($draftId);
            if ($selectedDraft) {
                $selectedDraft->last_opened_at = now();
                $selectedDraft->save();
            }
        }

        if ($folder === 'drafts' && $selectedDraft) {
            $selectedMessage = $selectedDraft;
            $threadMessages = collect([
                [
                    'id' => (int) $selectedDraft->id,
                    'kind' => 'draft',
                    'direction' => 'draft',
                    'subject' => (string) ($selectedDraft->subject ?: '(No subject)'),
                    'body' => (string) ($selectedDraft->message ?? ''),
                    'snippet' => Str::limit((string) ($selectedDraft->message ?? '(No draft body)'), 200),
                    'from_label' => (string) ($request->user()?->name ?? 'CRM user'),
                    'from_email' => (string) config('mail.from.address', ''),
                    'to_email' => (string) ($selectedDraft->recipient_email ?? ''),
                    'status' => 'draft',
                    'display_at' => optional($selectedDraft->updated_at ?? $selectedDraft->created_at)?->format('Y-m-d H:i') ?: '-',
                    'sort_at' => optional($selectedDraft->updated_at ?? $selectedDraft->created_at)?->timestamp ?? 0,
                    'is_selected' => true,
                ],
            ]);
        }

        $compose = [
            'draft_id' => old('draft_id', $selectedDraft?->id),
            'recipient_email' => old('recipient_email', (string) ($request->query('compose_to') ?? $selectedDraft?->recipient_email ?? '')),
            'reply_to' => old('reply_to', (string) ($selectedDraft?->reply_to ?? $defaultReplyTo)),
            'cc' => old('cc', (string) ($selectedDraft?->cc ?? '')),
            'bcc' => old('bcc', (string) ($selectedDraft?->bcc ?? '')),
            'client_project_id' => old('client_project_id', (string) ($request->query('compose_project_id') ?? $selectedDraft?->client_project_id ?? '')),
            'subject' => old('subject', (string) ($request->query('compose_subject') ?? $selectedDraft?->subject ?? '')),
            'message' => old('message', (string) ($request->query('compose_message') ?? $selectedDraft?->message ?? '')),
            'lead_id' => old('lead_id', (string) ($request->query('lead_id') ?? '')),
            'recipient_name' => old('recipient_name', (string) ($request->query('recipient_name') ?? '')),
            'ai_template' => old('ai_template', (string) ($request->query('compose_template') ?? 'custom')),
            'ai_goal' => old('ai_goal', (string) ($request->query('compose_goal') ?? '')),
        ];

        return view('admin.emails-mailbox', [
            'activeFolder' => $folder,
            'defaultRecipient' => $defaultRecipient,
            'defaultReplyTo' => $defaultReplyTo,
            'quickTemplates' => $quickTemplates,
            'projectOptions' => $projectOptions,
            'folderCounts' => $folderCounts,
            'mailboxItems' => $mailboxItems,
            'emailEventTimeline' => $eventTimeline,
            'selectedEmailEventTimeline' => $selectedTimeline,
            'selectedMessage' => $selectedMessage,
            'threadMessages' => $threadMessages,
            'openMessageId' => $openMessageId,
            'compose' => $compose,
        ]);
    }

    private function crmInboundReplyToAddress(): string
    {
        return \App\Services\CrmReplyToResolver::resolve();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,error?:string}
     */
    private function dispatchCrmEmail(array $payload): array
    {
        $recipient = trim((string) ($payload['recipient_email'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $replyTo = $this->crmInboundReplyToAddress();
        $cc = is_array($payload['cc'] ?? null) ? (array) $payload['cc'] : [];
        $bcc = is_array($payload['bcc'] ?? null) ? (array) $payload['bcc'] : [];
        $threadProjectId = !blank($payload['thread_project_id'] ?? null) ? (int) $payload['thread_project_id'] : null;
        $attachments = is_array($payload['attachments'] ?? null) ? (array) $payload['attachments'] : [];

        $emailLog = $this->createEmailLogEntry([
            'created_by' => $payload['created_by'] ?? null,
            'mode' => (string) ($payload['mode'] ?? 'custom'),
            'template_key' => blank($payload['template_key'] ?? null) ? null : (string) $payload['template_key'],
            'recipient_email' => $recipient,
            'reply_to' => $replyTo,
            'cc' => count($cc) > 0 ? implode(', ', $cc) : null,
            'bcc' => count($bcc) > 0 ? implode(', ', $bcc) : null,
            'subject' => $subject,
            'body_preview' => Str::limit($message, 700),
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
            'provider_status' => 'queued',
        ]);

        try {
            $mailer = Mail::to($recipient);
            if (count($cc) > 0) {
                $mailer->cc($cc);
            }
            if (count($bcc) > 0) {
                $mailer->bcc($bcc);
            }

            $mailer->send(new BrandedNotificationMail(
                subjectLine: $subject,
                heading: 'Message from Maccento CRM',
                bodyLines: $this->emailBodyToLines($message),
                intro: 'This message was sent from your CRM Email Center.',
                ctaLabel: 'Open Email Center',
                ctaUrl: route('admin.emails.sent'),
                footerNote: 'Need help? Reply to this email and our team will assist you.',
                emailLogId: $emailLog?->id,
                threadProjectId: $threadProjectId,
                replyToAddress: $replyTo,
                outboundAttachmentMeta: $attachments,
            ));

            if ($emailLog) {
                $emailLog->forceFill([
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => now(),
                    'provider_status' => 'processed',
                    'provider_last_event_at' => now(),
                ])->save();
            }

            $this->notificationService()->notifyInternal(
                'admin_email_sent',
                'CRM email sent',
                "Email sent to {$recipient}: {$subject}",
                route('admin.emails.sent'),
                ['recipient' => $recipient, 'subject' => $subject]
            );

            return ['ok' => true];
        } catch (Throwable $exception) {
            report($exception);

            if ($emailLog) {
                $emailLog->forceFill([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 500),
                    'provider_status' => 'failed',
                    'provider_last_event_at' => now(),
                ])->save();
            }

            return [
                'ok' => false,
                'error' => 'Email could not be sent. Please verify SMTP settings and try again.',
            ];
        }
    }

    /**
     * @param array<int,mixed> $files
     * @return array<int,array{path:string,name:string,mime:?string}>
     */
    private function normalizeOutboundAttachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $path = $file->getRealPath();
            if (!is_string($path) || $path === '' || !is_file($path)) {
                continue;
            }

            $attachments[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
            ];
        }

        return $attachments;
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
    private function logRequestEdit(string $type, int $requestId, ?int $clientId, ?User $actor, array $changes): void
    {
        if (!Schema::hasTable('request_edit_logs')) {
            return;
        }

        $payload = $this->filterTableColumns('request_edit_logs', [
            'request_type' => $type,
            'request_id' => $requestId,
            'entity_type' => $type,
            'entity_id' => $requestId,
            'client_id' => $clientId,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'action' => $changes['action'] ?? null,
            'summary' => $changes['summary'] ?? null,
            'ip_address' => null,
            'user_agent' => null,
            'changes' => $changes,
        ]);

        RequestEditLog::create($payload);
    }

    private function logActivity(Request $request, string $entityType, int $entityId, ?int $clientId, ?User $actor, string $action, string $summary, array $changes = []): void
    {
        if (!Schema::hasTable('request_edit_logs')) {
            return;
        }

        $payload = $this->filterTableColumns('request_edit_logs', [
            'request_type' => $entityType,
            'request_id' => $entityId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'client_id' => $clientId,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'action' => $action,
            'summary' => $summary,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'changes' => $changes,
        ]);

        RequestEditLog::create($payload);
    }

    private function resolvePortalClient(User $user, array $with = []): ?Client
    {
        return Client::query()
            ->with($with)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);

                if (!blank($user->phone)) {
                    $query->orWhere('phone', $user->phone);
                }
            })
            ->latest('id')
            ->first();
    }

    private function userLeadQuery(User $user)
    {
        return LeadProfile::query()
            ->where(function ($query) use ($user): void {
                $query->where('email', $user->email);

                if (!blank($user->phone)) {
                    $query->orWhere('phone', $user->phone);
                }
            });
    }

    private function userQuoteQuery(User $user)
    {
        return QuoteBuild::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('options->contact_email', $user->email);

                if (!blank($user->phone)) {
                    $query->orWhere('options->contact_phone', $user->phone);
                }
            });
    }

    private function buildUserPortalStats(?Client $client, $leadQuery, $quoteQuery): array
    {
        $projectStatuses = ['accepted', 'shooting', 'editing'];
        $activeProjects = 0;
        $deliveriesReady = 0;
        $unpaidInvoices = 0;
        $messageCount = 0;

        if ($client) {
            $activeProjects = ClientProject::query()
                ->where('client_id', $client->id)
                ->whereIn('status', $projectStatuses)
                ->count();

            $deliveriesReady = ClientProject::query()
                ->where('client_id', $client->id)
                ->whereHas('media', function ($query): void {
                    $query->where('type', 'final_zip');
                })
                ->count();

            $unpaidInvoices = ClientInvoice::query()
                ->where('client_id', $client->id)
                ->whereNotIn('status', ['paid'])
                ->count();

            $messageCount = ClientMessage::query()
                ->where('client_id', $client->id)
                ->count();
        }

        $pendingQuotes = (clone $quoteQuery)
            ->whereNotIn('status', ['booked', 'lost'])
            ->count();

        return [
            'active_projects' => $activeProjects,
            'unpaid_invoices' => $unpaidInvoices,
            'pending_quotes' => $pendingQuotes,
            'deliveries_ready' => $deliveriesReady,
            'message_count' => $messageCount,
            'lead_count' => (clone $leadQuery)->count(),
        ];
    }

    private function emptyPaginator(int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => request()->url(),
            'pageName' => $pageName,
        ]);
    }

    private function isOwnerRole(string $role): bool
    {
        $role = strtolower(trim($role));
        return in_array($role, ['owner', 'admin'], true);
    }

    private function adminRoles(): array
    {
        return ['owner', 'admin', 'manager'];
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

    private function assignableProjectUsers()
    {
        return User::query()
            ->whereIn('role', ['owner', 'admin', 'manager', 'photographer', 'editor'])
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function sanitizeAssignableUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', array_map('intval', $userIds))
            ->whereIn('role', ['owner', 'admin', 'manager', 'photographer', 'editor'])
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function ensureInternalUserCanCommentOnProject(Request $request, ClientProject $project): void
    {
        $this->ensureInternalUserCanAccessAssignedProject($request, $project);
    }

    private function ensureInternalUserCanUploadProjectMedia(Request $request, ClientProject $project): void
    {
        $role = strtolower(trim((string) $request->user()?->role));
        abort_unless(in_array($role, ['owner', 'admin', 'manager', 'photographer', 'editor'], true), 403);

        if (in_array($role, ['photographer', 'editor'], true)) {
            $this->ensureInternalUserCanAccessAssignedProject($request, $project);
        }
    }

    private function ensureInternalUserCanAccessAssignedProject(Request $request, ClientProject $project): void
    {
        $role = strtolower(trim((string) $request->user()?->role));

        if (in_array($role, ['owner', 'admin', 'manager'], true)) {
            return;
        }

        abort_unless(in_array($role, ['photographer', 'editor'], true), 403);

        $project->loadMissing('assignments');
        $assignedUserIds = $project->assignments->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        abort_unless(in_array((int) ($request->user()?->id ?? 0), $assignedUserIds, true), 403);
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
        $project->loadMissing('client:id,user_id,email,phone', 'assignments:user_id');
        $client = $project->client;

        $assignedUserIds = $project->assignments
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $isAssigned = in_array((int) ($user?->id ?? 0), $assignedUserIds, true);

        $allowed = $this->userMatchesClient($user, $client) || $isAssigned;

        abort_unless($allowed, 403);
    }

    private function ensureUserCanAccessInvoice(Request $request, ClientInvoice $invoice): void
    {
        $role = strtolower(trim((string) $request->user()?->role));
        if (in_array($role, ['owner', 'admin', 'manager', 'photographer', 'editor'], true)) {
            return;
        }

        $user = $request->user();
        $invoice->loadMissing('client:id,user_id,email,phone');
        $client = $invoice->client;
        $allowed = $this->userMatchesClient($user, $client);

        abort_unless($allowed, 403);
    }

    private function userMatchesClient(?User $user, ?Client $client): bool
    {
        if (!$user || !$client) {
            return false;
        }

        return (int) ($client->user_id ?? 0) === (int) $user->id
            || (!blank($client->email) && !blank($user->email) && strcasecmp((string) $client->email, (string) $user->email) === 0)
            || (!blank($client->phone) && !blank($user->phone) && (string) $client->phone === (string) $user->phone);
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

    private function resolveApiIntegrationSettings(): ApiIntegrationSetting
    {
        $settings = ApiIntegrationSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return ApiIntegrationSetting::query()->create([
            'paypal_sandbox' => true,
        ]);
    }

    private function resolveIntegrationValue(?string $stored, string $envKey): string
    {
        $stored = trim((string) $stored);
        if ($stored !== '') {
            return $stored;
        }

        return (string) env($envKey, '');
    }

    private function resolveIntegrationFlag(?bool $stored, string $envKey, bool $default = false): bool
    {
        if ($stored !== null) {
            return (bool) $stored;
        }

        return filter_var(env($envKey, $default), FILTER_VALIDATE_BOOLEAN);
    }

    private function resolveStripePublishableKey(): string
    {
        $settings = $this->resolveApiIntegrationSettings();
        return $this->resolveIntegrationValue($settings->stripe_publishable_key, 'STRIPE_PUBLISHABLE_KEY');
    }

    private function resolveStripeSecretKey(): string
    {
        $settings = $this->resolveApiIntegrationSettings();
        return $this->resolveIntegrationValue($settings->stripe_secret_key, 'STRIPE_SECRET_KEY');
    }

    private function resolvePayPalClientId(): string
    {
        $settings = $this->resolveApiIntegrationSettings();
        return $this->resolveIntegrationValue($settings->paypal_client_id, 'PAYPAL_CLIENT_ID');
    }

    private function resolvePayPalSecret(): string
    {
        $settings = $this->resolveApiIntegrationSettings();
        return $this->resolveIntegrationValue($settings->paypal_secret, 'PAYPAL_SECRET');
    }

    private function resolveMailSetting(?string $stored, string $envKey): string
    {
        $stored = trim((string) $stored);
        if ($stored !== '') {
            return $stored;
        }

        return (string) env($envKey, '');
    }
    private function resolvePayPalSandbox(): bool
    {
        $settings = $this->resolveApiIntegrationSettings();
        return $this->resolveIntegrationFlag($settings->paypal_sandbox, 'PAYPAL_SANDBOX', true);
    }
    private function resolveInvoiceSettings(): InvoiceSetting
    {
        $settings = InvoiceSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'stripe_enabled' => false,
            'paypal_enabled' => false,
            'manual_enabled' => true,
            'manual_instructions' => 'Pay by bank transfer or cash. Please reference your invoice number.',
            'include_tax_on_pdf' => false,
            'tax_rate_percent' => 0,
            'auto_email_on_invoice_create' => true,
            'reminder_enabled' => true,
            'reminder_days_before' => 3,
            'reminder_send_on_due_date' => true,
            'overdue_reminder_enabled' => true,
            'overdue_reminder_every_days' => 3,
        ];

        $allowed = array_filter($defaults, static function ($value, $column): bool {
            return Schema::hasColumn('invoice_settings', (string) $column);
        }, ARRAY_FILTER_USE_BOTH);

        return InvoiceSetting::query()->create($allowed);
    }

    private function buildApiDisplaySettings(): ApiIntegrationSetting
    {
        $settings = $this->resolveApiIntegrationSettings();
        $displaySettings = clone $settings;
        $displaySettings->stripe_publishable_key = $this->resolveStripePublishableKey();
        $displaySettings->stripe_secret_key = $this->resolveStripeSecretKey();
        $displaySettings->paypal_client_id = $this->resolvePayPalClientId();
        $displaySettings->paypal_secret = $this->resolvePayPalSecret();
        $displaySettings->paypal_sandbox = $this->resolvePayPalSandbox();
        $displaySettings->mail_mailer = $this->resolveMailSetting($settings->mail_mailer, 'MAIL_MAILER');
        $displaySettings->mail_host = $this->resolveMailSetting($settings->mail_host, 'MAIL_HOST');
        $displaySettings->mail_port = $settings->mail_port ?? (int) env('MAIL_PORT', 0);
        $displaySettings->mail_username = $this->resolveMailSetting($settings->mail_username, 'MAIL_USERNAME');
        $displaySettings->mail_password = $this->resolveMailSetting($settings->mail_password, 'MAIL_PASSWORD');
        $displaySettings->mail_encryption = $this->resolveMailSetting($settings->mail_encryption, 'MAIL_ENCRYPTION');
        $displaySettings->mail_from_address = $this->resolveMailSetting($settings->mail_from_address, 'MAIL_FROM_ADDRESS');
        $displaySettings->mail_from_name = $this->resolveMailSetting($settings->mail_from_name, 'MAIL_FROM_NAME');
        $displaySettings->inbound_mail_enabled = $this->resolveIntegrationFlag($settings->inbound_mail_enabled, 'INBOUND_MAIL_ENABLED', true);
        $displaySettings->inbound_mail_provider = $this->resolveIntegrationValue($settings->inbound_mail_provider, 'INBOUND_MAIL_PROVIDER');
        $displaySettings->inbound_mail_host = $this->resolveIntegrationValue($settings->inbound_mail_host, 'INBOUND_MAIL_HOST');
        $displaySettings->inbound_mail_port = $settings->inbound_mail_port ?? (int) env('INBOUND_MAIL_PORT', 0);
        $displaySettings->inbound_mail_encryption = $this->resolveIntegrationValue($settings->inbound_mail_encryption, 'INBOUND_MAIL_ENCRYPTION');
        $displaySettings->inbound_mail_username = $this->resolveIntegrationValue($settings->inbound_mail_username, 'INBOUND_MAIL_USERNAME');
        $displaySettings->inbound_mail_password = $this->resolveIntegrationValue($settings->inbound_mail_password, 'INBOUND_MAIL_PASSWORD');
        $displaySettings->inbound_mail_mailbox = $this->resolveIntegrationValue($settings->inbound_mail_mailbox, 'INBOUND_MAIL_MAILBOX');
        $displaySettings->inbound_mail_search = $this->resolveIntegrationValue($settings->inbound_mail_search, 'INBOUND_MAIL_SEARCH');
        $displaySettings->inbound_mail_max_per_run = $settings->inbound_mail_max_per_run ?? (int) env('INBOUND_MAIL_MAX_PER_RUN', 0);
        $displaySettings->inbound_mail_delete_after_process = $this->resolveIntegrationFlag($settings->inbound_mail_delete_after_process, 'INBOUND_MAIL_DELETE_AFTER_PROCESS', false);
        $displaySettings->media_disk = $this->resolveIntegrationValue($settings->media_disk, 'MEDIA_DISK');
        $displaySettings->s3_key = $this->resolveIntegrationValue($settings->s3_key, 'S3_ACCESS_KEY_ID');
        $displaySettings->s3_secret = $this->resolveIntegrationValue($settings->s3_secret, 'S3_SECRET_ACCESS_KEY');
        $displaySettings->s3_region = $this->resolveIntegrationValue($settings->s3_region, 'S3_REGION');
        $displaySettings->s3_bucket = $this->resolveIntegrationValue($settings->s3_bucket, 'S3_BUCKET');
        $displaySettings->s3_endpoint = $this->resolveIntegrationValue($settings->s3_endpoint, 'S3_ENDPOINT');
        $displaySettings->s3_path_style = $this->resolveIntegrationFlag($settings->s3_path_style, 'S3_PATH_STYLE', false);
        $displaySettings->outbound_webhook_enabled = $this->resolveIntegrationFlag($settings->outbound_webhook_enabled, 'OUTBOUND_WEBHOOK_ENABLED', false);
        $displaySettings->outbound_webhook_url = $this->resolveIntegrationValue($settings->outbound_webhook_url, 'OUTBOUND_WEBHOOK_URL');
        $displaySettings->outbound_webhook_secret = $this->resolveIntegrationValue($settings->outbound_webhook_secret, 'OUTBOUND_WEBHOOK_SECRET');
        $displaySettings->ai_provider = $this->resolveIntegrationValue($settings->ai_provider, 'AI_PROVIDER');
        $displaySettings->ai_model = $this->resolveIntegrationValue($settings->ai_model, 'AI_MODEL');
        $displaySettings->openai_api_key = $this->resolveIntegrationValue($settings->openai_api_key, 'OPENAI_API_KEY');
        $displaySettings->openai_base_url = $this->resolveIntegrationValue($settings->openai_base_url, 'OPENAI_BASE_URL');
        $displaySettings->openrouter_api_key = $this->resolveIntegrationValue($settings->openrouter_api_key, 'OPENROUTER_API_KEY');
        $displaySettings->openrouter_base_url = $this->resolveIntegrationValue($settings->openrouter_base_url, 'OPENROUTER_BASE_URL');
        $displaySettings->openrouter_model = $this->resolveIntegrationValue($settings->openrouter_model, 'OPENROUTER_MODEL');
        $displaySettings->gemini_api_key = $this->resolveIntegrationValue($settings->gemini_api_key, 'GEMINI_API_KEY');
        $displaySettings->gemini_base_url = $this->resolveIntegrationValue($settings->gemini_base_url, 'GEMINI_BASE_URL');
        $displaySettings->gemini_model = $this->resolveIntegrationValue($settings->gemini_model, 'GEMINI_MODEL');

        return $displaySettings;
    }

    /**
     * @return array<string,string>
     */
    private function currencyOptions(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'INR' => 'Indian Rupee',
            'BDT' => 'Bangladeshi Taka',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'SGD' => 'Singapore Dollar',
        ];
    }

    private function resolveCurrencySettings(): CurrencySetting
    {
        if (!Schema::hasTable('currency_settings')) {
            return new CurrencySetting([
                'default_currency' => 'USD',
                'enabled_currencies' => array_keys($this->currencyOptions()),
            ]);
        }

        $settings = CurrencySetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return CurrencySetting::query()->create([
            'default_currency' => 'USD',
            'enabled_currencies' => array_keys($this->currencyOptions()),
        ]);
    }

    private function resolveDefaultCurrency(): string
    {
        $settings = $this->resolveCurrencySettings();
        $default = strtoupper(trim((string) ($settings->default_currency ?? 'USD')));
        return $default !== '' ? $default : 'USD';
    }

    /**
     * @return array<int,string>
     */
    private function resolveAllowedCurrencyCodes(): array
    {
        $options = array_keys($this->currencyOptions());
        $settings = $this->resolveCurrencySettings();
        $enabled = array_map('strtoupper', array_map('trim', (array) ($settings->enabled_currencies ?? [])));
        $enabled = array_values(array_filter($enabled, static fn (string $code): bool => $code !== ''));
        $enabled = array_values(array_intersect($enabled, $options));

        return $enabled !== [] ? $enabled : $options;
    }

    private function resolveMediaDisk(): string
    {
        try {
            if (Schema::hasTable('api_integration_settings')) {
                $settings = ApiIntegrationSetting::query()->first();
                $mediaDisk = trim((string) ($settings?->media_disk ?? ''));
                if ($mediaDisk !== '') {
                    return $mediaDisk;
                }
            }
        } catch (Throwable $exception) {
            return 'public';
        }

        $envDisk = trim((string) env('MEDIA_DISK', ''));
        return $envDisk !== '' ? $envDisk : 'public';
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

    private function projectMediaUploadPath(ClientProject $project, ?User $user, string $bucket): string
    {
        $roleFolder = $this->projectMediaRoleFolder((string) ($user?->role ?? 'staff'));
        $userSlug = Str::slug((string) ($user?->name ?? ('user-' . (int) ($user?->id ?? 0))));
        $userSegment = 'user-' . (int) ($user?->id ?? 0) . ($userSlug !== '' ? '-' . $userSlug : '');

        return $this->projectMediaBasePath($project) . '/' . trim($bucket, '/') . '/' . $roleFolder . '/' . $userSegment;
    }

    private function projectMediaBucketForStage(string $stage): string
    {
        return match (strtolower(trim($stage))) {
            'edited' => 'edited-final',
            default => 'raw-footage',
        };
    }

    private function projectMediaRoleFolder(string $role): string
    {
        $normalized = strtolower(trim($role));

        return match ($normalized) {
            'owner', 'admin', 'manager', 'photographer', 'editor' => $normalized,
            default => 'staff',
        };
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
            $driver = (string) config("filesystems.disks.{$disk}.driver", 'local');
            if (!in_array($driver, ['local'], true)) {
                return null;
            }

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

    private function markInvoicePaid(ClientInvoice $invoice, ?User $actor, string $provider, ?string $reference, array $meta = [], ?string $note = null): void
    {
        $this->recordInvoicePayment(
            $invoice,
            (float) ($invoice->balance_due ?? $invoice->amount),
            $actor,
            $provider,
            $reference,
            $provider,
            $meta,
            $note
        );
    }

    private function recordInvoicePayment(
        ClientInvoice $invoice,
        float $amount,
        ?User $actor,
        string $provider,
        ?string $reference,
        string $method,
        array $meta = [],
        ?string $note = null
    ): void {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }

        $invoice->refresh();
        $invoiceAmount = (float) $invoice->amount;
        $amountPaid = (float) ($invoice->amount_paid ?? 0);
        $balanceDue = (float) ($invoice->balance_due ?? $invoiceAmount);
        if ($balanceDue <= 0) {
            if ($invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->save();
            }
            return;
        }

        $amountToApply = min($amount, $balanceDue);
        $newPaid = round($amountPaid + $amountToApply, 2);
        $newBalance = max(round($invoiceAmount - $newPaid, 2), 0.0);

        ClientInvoicePayment::create([
            'client_invoice_id' => $invoice->id,
            'created_by' => $actor?->id,
            'amount' => $amountToApply,
            'currency' => $invoice->currency,
            'provider' => $provider,
            'reference' => $reference,
            'method' => $method,
            'meta' => $meta,
            'paid_at' => now(),
        ]);

        $invoice->amount_paid = $newPaid;
        $invoice->balance_due = $newBalance;
        $invoice->payment_provider = $provider;
        $invoice->payment_reference = $reference;
        $invoice->payment_method = $method;
        $invoice->payment_meta = $meta;

        if ($newBalance <= 0) {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
        } else {
            $invoice->paid_at = null;
            $invoice->status = $invoice->due_date && $invoice->due_date->isPast() ? 'overdue' : 'partial';
        }

        $invoice->save();

        $invoice->loadMissing(['client']);
        if ($invoice->client) {
            ClientMessage::create([
                'client_id' => $invoice->client_id,
                'client_project_id' => $invoice->client_project_id,
                'sender_user_id' => $actor?->id,
                'sender_role' => $actor?->role ?: 'system',
                'message' => trim('Payment received for invoice ' . $invoice->invoice_number . ' via ' . strtoupper($provider) . ': ' . number_format($amountToApply, 2) . ' ' . $invoice->currency . ($note ? (' (' . $note . ')') : '')),
                'sent_at' => now(),
            ]);
        }

        $this->notificationService()->notifyInternal(
            'invoice_payment_recorded',
            'Invoice payment recorded',
            'Invoice ' . $invoice->invoice_number . ' received ' . number_format($amountToApply, 2) . ' ' . $invoice->currency . '.',
            route('admin.invoices.index'),
            ['invoice_id' => $invoice->id]
        );

        $this->logActivity(
            request(),
            'invoice',
            $invoice->id,
            $invoice->client_id,
            $actor,
            'payment',
            'Payment recorded for invoice ' . $invoice->invoice_number,
            [
                'amount' => $amountToApply,
                'currency' => $invoice->currency,
                'method' => $method,
            ]
        );

        app(OutboundWebhookService::class)->send('invoice.payment_recorded', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_id' => $invoice->client_id,
            'amount' => $amountToApply,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
            'balance_due' => $invoice->balance_due,
            'paid_at' => $invoice->paid_at?->toIso8601String(),
        ]);
    }

    private function paypalBaseUrl(): string
    {
        $useSandbox = $this->resolvePayPalSandbox();
        return $useSandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function isPaymentDemoMode(): bool
    {
        return filter_var(env('PAYMENTS_DEMO_MODE', true), FILTER_VALIDATE_BOOLEAN);
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

