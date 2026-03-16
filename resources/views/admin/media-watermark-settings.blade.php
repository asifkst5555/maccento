@extends('layouts.panel', [
  'title' => 'Watermark Settings',
  'heading' => 'Watermark Settings',
  'subheading' => 'Upload watermark logo and control position/size for unpaid gallery previews.',
])

@section('content')
@include('admin.partials.watermark-settings', [
  'settings' => $settings,
  'unpaidImageTotal' => $unpaidImageTotal,
  'upToDateWatermarks' => $upToDateWatermarks,
  'pendingRebuild' => $pendingRebuild,
  'logoExists' => $logoExists,
])
@endsection
