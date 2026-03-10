<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($title ?? 'CRM Panel'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="<?php echo e(asset('assets/media/favicon.ico')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('assets/css/site.css')); ?>?v=<?php echo e(@filemtime(public_path('assets/css/site.css')) ?: time()); ?>">
  <style>
    :root {
      --panel-font-body: 'Manrope', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      --panel-font-display: 'Sora', 'Manrope', 'Segoe UI', sans-serif;
      --panel-font-mono: 'JetBrains Mono', Consolas, 'Courier New', monospace;
      --panel-copy-color: #213a56;
      --panel-copy-muted: #5f738d;
    }

    body.panel-page,
    body.panel-page input,
    body.panel-page select,
    body.panel-page textarea,
    body.panel-page button {
      font-family: var(--panel-font-body);
      color: var(--panel-copy-color);
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }

    body.panel-page h1,
    body.panel-page h2,
    body.panel-page h3,
    body.panel-page h4,
    body.panel-page h5,
    body.panel-page h6,
    body.panel-page .panel-title,
    body.panel-page .panel-section-title,
    body.panel-page .panel-brand-name,
    body.panel-page .panel-nav-group-toggle-text {
      font-family: var(--panel-font-display);
      letter-spacing: -0.01em;
      color: #10233a;
    }

    body.panel-page .panel-title {
      font-weight: 700;
      font-size: clamp(1.35rem, 1.2rem + 0.8vw, 1.9rem);
      line-height: 1.15;
    }

    body.panel-page .panel-sub,
    body.panel-page .panel-muted,
    body.panel-page .panel-brand-role,
    body.panel-page .panel-kpi-label {
      color: var(--panel-copy-muted);
      font-weight: 400;
      letter-spacing: 0.01em;
    }

    body.panel-page .panel-nav-text,
    body.panel-page .panel-subnav-link,
    body.panel-page .panel-link,
    body.panel-page .panel-btn {
      font-weight: 500;
      letter-spacing: 0.01em;
    }

    body.panel-page .panel-nav-count,
    body.panel-page .panel-subnav-count,
    body.panel-page .panel-badge,
    body.panel-page .panel-notify-count {
      font-family: var(--panel-font-display);
      font-weight: 600;
      letter-spacing: 0.02em;
    }

    body.panel-page .panel-table,
    body.panel-page .panel-table th,
    body.panel-page .panel-table td {
      font-size: 0.9rem;
      line-height: 1.45;
    }

    body.panel-page .panel-table th {
      font-family: var(--panel-font-display);
      font-weight: 600;
    }

    body.panel-page .panel-table td,
    body.panel-page p,
    body.panel-page span,
    body.panel-page label,
    body.panel-page a,
    body.panel-page button {
      font-weight: 400;
    }

    body.panel-page .panel-btn,
    body.panel-page .panel-link,
    body.panel-page .panel-nav-text,
    body.panel-page .panel-subnav-link {
      font-weight: 500;
    }

    body.panel-page code,
    body.panel-page .panel-code,
    body.panel-page .panel-invoice-number {
      font-family: var(--panel-font-mono);
      font-weight: 600;
      letter-spacing: 0.01em;
    }

    /* Global vertical rhythm for consistent premium spacing across panel pages */
    body.panel-page .panel-shell {
      gap: 1rem;
    }

    body.panel-page .panel-card {
      padding: 1rem;
      border-radius: 14px;
    }

    body.panel-page .panel-grid {
      gap: 0.85rem;
    }

    body.panel-page .panel-grid.panel-grid-kpi {
      margin-bottom: 1rem;
    }

    body.panel-page .panel-section-title,
    body.panel-page .panel-title,
    body.panel-page .panel-kpi-value {
      margin-bottom: 0.55rem;
    }

    body.panel-page .panel-sub,
    body.panel-page .panel-muted,
    body.panel-page .panel-kpi-label {
      margin-bottom: 0.35rem;
    }

    body.panel-page .panel-form-row {
      gap: 0.6rem;
      margin-bottom: 0.75rem;
    }

    body.panel-page .panel-stack {
      gap: 0.7rem;
    }

    body.panel-page .panel-table-wrap {
      margin-top: 0.8rem;
    }

    body.panel-page .panel-table th,
    body.panel-page .panel-table td {
      padding-top: 0.72rem;
      padding-bottom: 0.72rem;
    }

    body.panel-page .panel-btn {
      min-height: 2.25rem;
      padding-inline: 0.85rem;
    }

    body.panel-page .panel-btn-primary,
    body.panel-page .panel-btn-primary:link,
    body.panel-page .panel-btn-primary:visited {
      color: #ffffff !important;
    }

    body.panel-page .panel-btn-danger,
    body.panel-page .panel-btn-danger:link,
    body.panel-page .panel-btn-danger:visited {
      color: #ffffff !important;
    }

    body.panel-page .panel-btn-primary .panel-nav-icon,
    body.panel-page .panel-btn-primary svg,
    body.panel-page .panel-btn-danger .panel-nav-icon,
    body.panel-page .panel-btn-danger svg {
      color: inherit;
    }

    body.panel-page .panel-assistant-launch {
      position: fixed;
      right: 24px;
      bottom: 20px;
      z-index: 88;
      width: 60px;
      height: 60px;
      margin-left: auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #931b24;
      border-radius: 999px;
      background: linear-gradient(135deg, #8d141f, #c21f2d);
      box-shadow: 0 14px 28px rgba(132, 16, 28, 0.34);
      color: #ffffff;
      cursor: pointer;
      overflow: visible;
    }

    body.panel-page .panel-assistant-launch::before,
    body.panel-page .panel-assistant-launch::after {
      content: "";
      position: absolute;
      inset: -6px;
      border: 2px solid rgba(194, 31, 45, 0.48);
      border-radius: 999px;
      opacity: 0;
      pointer-events: none;
    }

    body.panel-page .panel-assistant-launch::before {
      animation: panel-assistant-pulse 2.4s ease-out infinite;
    }

    body.panel-page .panel-assistant-launch::after {
      animation: panel-assistant-pulse 2.4s ease-out infinite 0.9s;
    }

    body.panel-page .panel-assistant-launch[aria-expanded="true"]::before,
    body.panel-page .panel-assistant-launch[aria-expanded="true"]::after {
      animation: none;
      opacity: 0;
    }

    body.panel-page .panel-assistant-launch svg {
      width: 28px;
      height: 28px;
      flex: 0 0 auto;
    }

    body.panel-page .panel-assistant-launch-text {
      display: none;
    }

    @keyframes panel-assistant-pulse {
      0% {
        transform: scale(0.88);
        opacity: 0.75;
      }

      70% {
        transform: scale(1.2);
        opacity: 0;
      }

      100% {
        transform: scale(1.2);
        opacity: 0;
      }
    }

    body.panel-page .panel-assistant {
      position: fixed;
      right: 24px;
      top: calc(var(--panel-topbar-fixed-height, 104px) + 18px);
      bottom: 92px;
      z-index: 89;
      width: min(370px, calc(100vw - 28px));
      max-height: none;
      display: none;
      grid-template-rows: auto minmax(0, 1fr) auto;
      border: 1px solid #d63b4c;
      border-radius: 18px;
      background: #ffffff;
      box-shadow: 0 20px 38px rgba(72, 10, 17, 0.26);
      overflow: hidden;
    }

    body.panel-page .panel-assistant.is-open {
      display: grid;
    }

    body.panel-page .panel-assistant-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      padding: 13px 14px;
      border-bottom: 2px solid #c21f2d;
      background: #111111;
      color: #ffffff;
    }

    body.panel-page .panel-assistant-head-main {
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    body.panel-page .panel-assistant-head-icon {
      width: 34px;
      height: 34px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      border-radius: 10px;
      background: linear-gradient(135deg, #8d141f, #c21f2d);
      color: #ffffff;
    }

    body.panel-page .panel-assistant-head-icon svg {
      width: 16px;
      height: 16px;
    }

    body.panel-page .panel-assistant-head-copy {
      min-width: 0;
    }

    body.panel-page .panel-assistant-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      margin-bottom: 6px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      color: rgba(255, 255, 255, 0.84);
      font-size: 0.62rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    body.panel-page .panel-assistant-title {
      margin: 0;
      font-family: var(--panel-font-display);
      font-size: 0.98rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.2;
    }

    body.panel-page .panel-assistant-sub {
      margin: 4px 0 0;
      font-size: 0.72rem;
      line-height: 1.45;
      color: rgba(255, 255, 255, 0.86);
    }

    body.panel-page .panel-assistant-close {
      width: 30px;
      height: 30px;
      flex: 0 0 auto;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.08);
      color: #ffffff;
      cursor: pointer;
      font-size: 1.35rem;
      line-height: 1;
    }

    body.panel-page .panel-assistant-close:hover {
      background: rgba(255, 255, 255, 0.16);
    }

    body.panel-page .panel-assistant-body {
      overflow-y: auto;
      padding: 12px;
      background: linear-gradient(180deg, #fff8f9 0%, #ffeef0 100%);
    }

    body.panel-page .panel-assistant-stack {
      display: grid;
      gap: 10px;
    }

    body.panel-page .panel-assistant-empty {
      margin: 0;
      max-width: 100%;
      padding: 10px 11px;
      border: 1px solid #f3b4bc;
      border-radius: 10px;
      background: #ffffff;
      color: #7f1a24;
      font-size: 0.82rem;
      line-height: 1.45;
    }

    body.panel-page .panel-assistant-msg {
      max-width: 90%;
      padding: 10px 11px;
      border-radius: 10px;
      font-size: 0.82rem;
      line-height: 1.45;
      white-space: pre-wrap;
      word-break: break-word;
    }

    body.panel-page .panel-assistant-msg.is-user {
      margin-left: auto;
      background: #b71c2a;
      color: #ffffff;
      border: 1px solid #8f1420;
    }

    body.panel-page .panel-assistant-msg.is-assistant {
      margin-right: auto;
      background: #ffffff;
      border: 1px solid #e7b0b7;
      color: #2b0c10;
    }

    body.panel-page .panel-assistant-foot {
      padding: 12px;
      border-top: 1px solid #efc3c8;
      background: #ffffff;
    }

    body.panel-page .panel-assistant-meta {
      display: grid;
      gap: 4px;
      margin-bottom: 10px;
      font-size: 0.78rem;
      line-height: 1.45;
      color: #5f1821;
    }

    body.panel-page .panel-assistant-form {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    body.panel-page .panel-assistant-input {
      flex: 1;
      min-width: 0;
      min-height: 0;
      border-radius: 10px;
      border: 1px solid #dc8f99;
      background: #ffffff;
      padding: 10px 11px;
      color: #2b0c10;
      box-shadow: none;
    }

    body.panel-page .panel-assistant-input:focus {
      outline: none;
      border-color: #be1e2d;
      box-shadow: 0 0 0 3px rgba(190, 30, 45, 0.16);
    }

    body.panel-page .panel-assistant-send {
      min-width: 92px;
      min-height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      align-self: center;
      justify-self: auto;
      padding: 10px 13px;
      border-radius: 10px;
    }

    body.panel-page .panel-assistant-send:hover {
      background: #a11824;
    }

    body.panel-page .panel-assistant-send:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    body.panel-page .panel-assistant-status {
      min-height: 1em;
      white-space: normal;
      font-weight: 600;
    }

    body.panel-page .panel-sidebar,
    body.panel-page .panel-sidebar .panel-brand-name,
    body.panel-page .panel-sidebar .panel-brand-role,
    body.panel-page .panel-sidebar .panel-nav-group-title,
    body.panel-page .panel-sidebar .panel-nav-text,
    body.panel-page .panel-sidebar .panel-nav-group-toggle-text,
    body.panel-page .panel-sidebar .panel-nav-link,
    body.panel-page .panel-sidebar .panel-subnav-link,
    body.panel-page .panel-sidebar .panel-nav-count,
    body.panel-page .panel-sidebar .panel-subnav-count,
    body.panel-page .panel-sidebar .panel-subnav-toggle {
      color: #d9e6f7;
    }

    body.panel-page .panel-sidebar .panel-nav-link.is-active,
    body.panel-page .panel-sidebar .panel-subnav-link.is-active,
    body.panel-page .panel-sidebar .panel-nav-link:hover,
    body.panel-page .panel-sidebar .panel-subnav-link:hover {
      color: #ffffff;
    }

    body.panel-page .panel-sidebar .panel-nav-group-title {
      opacity: 0.92;
    }

    body.panel-page .panel-brand {
      position: sticky !important;
      top: 0 !important;
      z-index: 20 !important;
      padding: 18px 16px !important;
      background: linear-gradient(180deg, rgba(17, 24, 39, 0.98) 0%, rgba(20, 29, 45, 0.96) 100%) !important;
      backdrop-filter: blur(12px);
    }

    body.panel-page .panel-sidebar .panel-nav {
      padding: 10px 8px !important;
      gap: 2px !important;
      align-content: start !important;
    }

    body.panel-page .panel-sidebar .panel-nav-group-title {
      margin: 10px 10px 4px !important;
      line-height: 1 !important;
    }

    body.panel-page .panel-sidebar .panel-nav-link {
      padding: 8px 10px !important;
      gap: 9px !important;
      font-size: 13px !important;
    }

    body.panel-page .panel-sidebar .panel-nav-count {
      min-width: 19px !important;
      height: 19px !important;
      padding: 0 5px !important;
      font-size: 10px !important;
    }

    body.panel-page .panel-sidebar .panel-nav-head {
      gap: 6px !important;
    }

    body.panel-page .panel-sidebar .panel-subnav-toggle {
      width: 28px !important;
      height: 28px !important;
    }

    body.panel-page .panel-sidebar .panel-subnav {
      margin: 0 0 4px 30px !important;
      padding: 5px !important;
      border-radius: 11px !important;
    }

    body.panel-page .panel-sidebar .panel-sidebar-foot {
      padding: 12px 10px 16px !important;
    }

    body.panel-page .panel-sidebar .panel-subnav-toggle,
    body.panel-page .panel-sidebar .panel-collapse-toggle {
      color: #ffffff !important;
    }

    body.panel-page .panel-sidebar .panel-subnav-toggle svg,
    body.panel-page .panel-sidebar .panel-collapse-toggle svg,
    body.panel-page .panel-sidebar .panel-subnav-toggle svg path,
    body.panel-page .panel-sidebar .panel-collapse-toggle svg path {
      color: #ffffff !important;
      stroke: #ffffff !important;
      opacity: 1 !important;
    }

    @media (max-width: 1100px) {
      body.panel-page .panel-sidebar .panel-nav {
        padding: 14px 10px 18px !important;
      }

      body.panel-page .panel-sidebar .panel-nav-link,
      body.panel-page .panel-sidebar .panel-subnav-link {
        min-height: 42px;
      }

      body.panel-page .panel-sidebar .panel-sidebar-foot {
        padding: 12px 12px 18px !important;
      }
    }

    @media (max-width: 768px) {
      body.panel-page .panel-assistant-launch {
        right: 14px;
        bottom: 14px;
        width: 56px;
        height: 56px;
      }

      body.panel-page .panel-assistant-launch svg {
        width: 26px;
        height: 26px;
      }

      body.panel-page .panel-assistant {
        right: 10px;
        left: 10px;
        top: 84px;
        bottom: 74px;
        width: auto;
        max-height: none;
        border-radius: 16px;
      }

      body.panel-page .panel-assistant-form {
        flex-direction: column;
        align-items: stretch;
      }

      body.panel-page .panel-assistant-send {
        width: 100%;
        min-height: 44px;
      }

      body.panel-page .panel-card {
        padding: 0.8rem;
      }

      body.panel-page .panel-grid {
        gap: 0.65rem;
      }

      body.panel-page .panel-form-row {
        gap: 0.5rem;
        margin-bottom: 0.65rem;
      }
    }
  
    .panel-modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(12, 21, 33, 0.45);
      z-index: 2000;
      padding: 1.5rem;
    }

    .panel-modal.is-open {
      display: flex;
    }

    .panel-modal-card {
      width: min(520px, 92vw);
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid #d7e2ef;
      box-shadow: 0 24px 60px rgba(12, 21, 33, 0.18);
      padding: 1.2rem 1.35rem;
      display: grid;
      gap: 0.85rem;
    }

    .panel-modal-title {
      margin: 0;
      font-size: 1.15rem;
      color: #10233a;
    }

    .panel-modal-body {
      margin: 0;
      color: #5f738d;
      line-height: 1.5;
    }

    .panel-modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.6rem;
    }

    .panel-btn-danger {
      background: #b71d34;
      border-color: #9f162b;
      color: #ffffff;
    }

    /* Responsive fixes for laptop widths */
    .panel-main {
      min-width: 0;
    }

    body.panel-page .panel-shell {
      min-width: 0;
      width: 100%;
    }

    body.panel-page .panel-table-wrap {
      overflow-x: auto;
    }

    body.panel-page .panel-table {
      min-width: 100%;
    }

    @media (max-width: 1366px) {
      body.panel-page .panel-form-row {
        flex-wrap: wrap;
      }

      body.panel-page .panel-form-row > * {
        flex: 1 1 220px;
        min-width: 220px;
      }

      body.panel-page .panel-actions {
        flex-wrap: wrap;
        justify-content: flex-end;
      }
    }

    /* Shared corporate shell styling for admin + user workspaces */
    .corp-admin-shell {
      --corp-ink: #10233a;
      --corp-ink-soft: #586b83;
      --corp-line: #d6e0ec;
      --corp-surface: #ffffff;
      --corp-soft: #f3f7fc;
      --corp-accent: #c11f37;
      --corp-shadow: 0 14px 30px rgba(16, 35, 58, 0.08);
    }

    .corp-admin-shell .panel-card {
      border: 1px solid var(--corp-line);
      border-radius: 14px;
      background: var(--corp-surface);
      box-shadow: var(--corp-shadow);
    }

    .corp-admin-shell .panel-section-title,
    .corp-admin-shell .panel-kanban-col-head h3,
    .corp-admin-shell .panel-kanban-title {
      color: var(--corp-ink);
    }

    .corp-admin-shell .panel-kpi-label,
    .corp-admin-shell .panel-muted {
      color: var(--corp-ink-soft);
    }

    .corp-admin-shell .panel-kpi-label {
      font-weight: 700;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      font-size: 0.72rem;
    }

    .corp-admin-shell .panel-link {
      color: #143557;
      font-weight: 600;
      text-decoration: none;
    }

    .corp-admin-shell .panel-link:hover {
      color: var(--corp-accent);
    }

    .corp-admin-shell .panel-input,
    .corp-admin-shell .panel-select,
    .corp-admin-shell .panel-textarea {
      border-radius: 10px;
      border: 1px solid #c9d6e5;
      background-color: #fff;
    }

    .corp-admin-shell .panel-select {
      background-position: right 19px center !important;
      padding-right: 42px !important;
      background-size: 18px 18px !important;
    }

    .corp-admin-shell .panel-multi {
      border: 1px solid #c9d6e5;
      border-radius: 10px;
      padding: 0.65rem;
      background: #ffffff;
      display: grid;
      gap: 0.45rem;
      max-height: 220px;
      overflow: auto;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .corp-admin-shell .panel-multi label {
      display: grid;
      grid-template-columns: 18px minmax(0, 1fr);
      gap: 0.55rem;
      align-items: center;
      padding: 0.4rem 0.55rem;
      border-radius: 8px;
      border: 1px solid transparent;
      background: #f8fbff;
      color: var(--corp-ink);
      font-weight: 600;
      font-size: 0.88rem;
    }

    @media (max-width: 960px) {
      .corp-admin-shell .panel-multi {
        grid-template-columns: minmax(0, 1fr);
      }
    }

    .corp-admin-shell .project-dates-row {
      align-items: flex-end;
    }

    .corp-admin-shell .project-dates-row > .panel-stack {
      flex: 1 1 0;
    }

    .corp-admin-shell .panel-multi label:hover {
      border-color: #cfd9e6;
    }

    .corp-admin-shell .panel-multi input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--corp-accent);
    }

    .corp-admin-shell .panel-btn {
      border-radius: 10px;
      border: 1px solid #bfcfe0;
      font-weight: 600;
    }

    .corp-admin-shell .panel-btn-primary {
      background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
      border-color: #a5172d;
    }

    .corp-admin-shell .panel-btn-icon {
      width: 48px;
      height: 48px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      border: 1px solid #f0c7cd;
      background: #fff5f6;
      color: #b71d34;
    }

    .corp-admin-shell .panel-btn-icon svg {
      width: 32px !important;
      height: 32px !important;
    }

    .corp-admin-shell .panel-btn-icon:hover {
      background: #fdecee;
      border-color: #e7aeb8;
    }

    .corp-admin-shell .panel-btn-danger {
      background: #b71d34;
      border-color: #9f162b;
      color: #ffffff;
    }

    .corp-admin-shell .panel-sticky-filters {
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
      border: 1px solid #d9e3ef;
      border-radius: 14px;
      padding: 0.8rem;
    }

    .corp-admin-shell .panel-table-wrap {
      border: 1px solid var(--corp-line);
      border-radius: 12px;
      overflow: hidden;
    }

    .corp-admin-shell .panel-table {
      min-width: 100%;
    }

    .corp-admin-shell .panel-table thead th {
      background: var(--corp-soft);
      color: #324963;
    }

    .corp-admin-shell .panel-table tbody tr:nth-child(even) {
      background: #fbfdff;
    }

    .corp-admin-shell .panel-badge {
      border-radius: 999px;
      border: 1px solid #c5d3e3;
      padding: 2px 10px;
    }

    .corp-admin-shell .panel-row-overdue {
      background: #fff7f8;
    }

    /* Messages / Chats shared styles */
    .messages-chat-shell {
      --messages-bg: radial-gradient(circle at top, #f7f8fb 0%, #eef2f7 55%, #e9eef5 100%);
      --messages-ink: #0f2137;
      --messages-muted: #5f7286;
      --messages-line: #d6dde7;
      --messages-panel: #ffffff;
      --messages-panel-alt: #f4f7fb;
      --messages-shadow: 0 18px 38px rgba(15, 33, 55, 0.08);
      --messages-accent: #b71c2d;
      --messages-accent-dark: #8f1624;
      --messages-danger: #b71c2d;
      --messages-danger-dark: #8f1624;
      display: grid;
      gap: 1rem;
    }

    .messages-chat-shell .panel-card {
      border: 1px solid var(--messages-line);
      border-radius: 20px;
      background: var(--messages-panel);
      box-shadow: var(--messages-shadow);
    }

    .messages-chat-shell .panel-input,
    .messages-chat-shell .panel-select,
    .messages-chat-shell .panel-textarea,
    .messages-chat-shell .panel-btn {
      border-radius: 14px;
      background-color: #fff;
    }

    .messages-chat-shell .panel-btn-primary {
      background: linear-gradient(135deg, var(--messages-accent) 0%, var(--messages-accent-dark) 100%);
      border-color: transparent;
      color: #ffffff;
      box-shadow: 0 14px 24px rgba(11, 61, 145, 0.2);
    }

    .messages-chat-shell .panel-btn-primary:hover {
      filter: brightness(0.98);
    }

    .messages-chat-shell .panel-btn {
      border-color: #cfd7e3;
      color: var(--messages-ink);
    }

    .messages-chat-layout {
      display: grid;
      grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
      gap: 1rem;
      min-height: 72vh;
    }

    .messages-thread-panel {
      padding: 1rem;
      display: grid;
      grid-template-rows: auto auto minmax(0, 1fr);
      gap: 0.9rem;
      background: linear-gradient(180deg, #ffffff 0%, #f5f7fb 100%);
      color: var(--messages-ink);
    }

    .messages-thread-panel .panel-kpi-label,
    .messages-thread-panel .panel-muted {
      color: var(--messages-muted);
    }

    .messages-thread-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .messages-thread-title {
      margin: 0;
      font-size: 1.55rem;
      line-height: 1.1;
      color: var(--messages-ink);
    }

    .messages-thread-sub {
      margin: 0.3rem 0 0;
      color: var(--messages-muted);
      font-size: 0.94rem;
    }

    .messages-thread-search {
      display: grid;
      gap: 0.7rem;
    }

    .messages-chat-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
    }

    .messages-chat-tab {
      padding: 0.55rem 1rem;
      border-radius: 12px;
      border: 1px solid var(--messages-line);
      background: #ffffff;
      color: var(--messages-ink);
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .messages-chat-tab:hover {
      border-color: #bfcad8;
      box-shadow: 0 10px 18px rgba(15, 33, 55, 0.08);
    }

    .messages-chat-tab.is-active {
      background: linear-gradient(135deg, var(--messages-accent) 0%, var(--messages-accent-dark) 100%);
      border-color: transparent;
      color: #ffffff;
      box-shadow: 0 14px 24px rgba(183, 28, 45, 0.2);
    }

    .messages-thread-search .panel-form-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto auto;
      align-items: center;
      gap: 0.6rem;
    }

    .messages-thread-clear {
      text-decoration: none;
      padding: 0.65rem 1.1rem;
      border-radius: 14px;
      border: 1px solid #cfd7e3;
      background: #ffffff;
      color: var(--messages-ink);
      font-weight: 600;
      font-size: 0.9rem;
      line-height: 1;
      transition: border-color 0.18s ease, background-color 0.18s ease;
    }

    .messages-thread-clear:hover {
      border-color: #bfcad8;
      background: #f8fafc;
    }

    .messages-thread-list {
      display: grid;
      gap: 0.7rem;
      overflow: auto;
      padding-right: 4px;
    }

    .messages-thread-divider {
      margin-top: 0.2rem;
      padding-top: 0.6rem;
      border-top: 1px dashed #d7dfeb;
      color: var(--messages-muted);
      font-size: 0.74rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .messages-thread-item {
      display: flex;
      gap: 0.8rem;
      padding: 0.7rem;
      border-radius: 16px;
      border: 1px solid #e0e7f1;
      background: #ffffff;
      text-decoration: none;
      color: inherit;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .messages-thread-item:hover {
      border-color: #c5d2e4;
      box-shadow: 0 16px 28px rgba(16, 33, 55, 0.08);
      transform: translateY(-1px);
    }

    .messages-thread-item.is-active {
      border-color: transparent;
      background: linear-gradient(135deg, rgba(183, 28, 45, 0.12) 0%, rgba(183, 28, 45, 0.08) 100%);
      box-shadow: 0 16px 28px rgba(183, 28, 45, 0.15);
    }

    .messages-thread-avatar {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: #edf2f8;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: #344a65;
    }

    .messages-thread-main {
      display: grid;
      gap: 0.35rem;
      min-width: 0;
    }

    .messages-thread-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.6rem;
    }

    .messages-thread-name {
      margin: 0;
      font-size: 0.98rem;
      font-weight: 700;
      color: var(--messages-ink);
    }

    .messages-thread-time {
      font-size: 0.75rem;
      color: var(--messages-muted);
    }

    .messages-thread-meta {
      margin: 0;
      font-size: 0.82rem;
      color: var(--messages-muted);
    }

    .messages-thread-preview {
      margin: 0;
      font-size: 0.82rem;
      color: var(--messages-ink);
    }

    .messages-chat-panel {
      padding: 1.2rem 1.3rem;
      display: grid;
      gap: 1rem;
      min-height: 100%;
      background: var(--messages-bg);
    }

    .messages-chat-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.1rem;
      border-radius: 16px;
      border: 1px solid #d9e2ef;
      background: #ffffff;
    }

    .messages-chat-head h2 {
      margin: 0;
      font-size: 1.2rem;
      color: var(--messages-ink);
    }

    .messages-chat-head p {
      margin: 0.25rem 0 0;
      color: var(--messages-muted);
    }

    .messages-chat-head-meta {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .messages-chat-stream {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      max-height: 48vh;
      overflow: auto;
      padding: 0.2rem 0.2rem 0.2rem 0.6rem;
    }

    .messages-chat-date {
      align-self: center;
      padding: 0.3rem 0.7rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.85);
      border: 1px solid #dbe3ef;
      color: var(--messages-muted);
      font-size: 0.78rem;
      font-weight: 600;
    }

    .messages-chat-row {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      max-width: min(75%, 460px);
    }

    .messages-chat-row.is-admin {
      align-self: flex-end;
      text-align: right;
    }

    .messages-chat-bubble {
      padding: 0.7rem 0.9rem;
      border-radius: 14px;
      background: #ffffff;
      border: 1px solid #dfe7f1;
      color: var(--messages-ink);
      font-weight: 500;
      line-height: 1.45;
    }

    .messages-chat-row.is-admin .messages-chat-bubble {
      background: #b71c2d;
      color: #ffffff;
      border-color: #b71c2d;
      box-shadow: 0 12px 20px rgba(183, 28, 45, 0.2);
    }

    .messages-chat-note {
      display: inline-flex;
      gap: 0.35rem;
      align-items: center;
      color: var(--messages-muted);
      font-size: 0.75rem;
    }

    .messages-empty-state {
      display: grid;
      place-items: center;
      padding: 2rem 1rem;
      text-align: center;
      color: var(--messages-muted);
      font-size: 0.9rem;
    }

    .messages-chat-compose {
      display: grid;
      gap: 0.65rem;
      padding: 0.9rem 1rem;
      border-radius: 16px;
      border: 1px solid #d9e2ef;
      background: #ffffff;
    }

    .messages-chat-compose-top {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 0.8rem;
      align-items: center;
    }

    .messages-chat-compose .panel-textarea {
      min-height: 120px;
    }

    @media (max-width: 1100px) {
      .messages-chat-layout {
        grid-template-columns: 320px minmax(0, 1fr);
      }
    }

    @media (max-width: 960px) {
      .messages-chat-layout {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .messages-thread-panel,
      .messages-chat-panel {
        min-height: auto;
      }

      .messages-chat-stream {
        max-height: 40vh;
      }
    }

    @media (max-width: 640px) {
      .messages-thread-search .panel-form-row {
        grid-template-columns: 1fr;
      }

      .messages-chat-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .messages-chat-compose-top {
        grid-template-columns: 1fr;
      }

      .messages-chat-row {
        max-width: 100%;
      }
    }

    /* Media delivery shared styles */
    .media-delivery-upload-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      margin-top: 12px;
    }

    .media-delivery-upload-card {
      border: 1px solid #d8e1ec;
      border-radius: 12px;
      background: #f9fbff;
      padding: 12px;
    }

    .media-delivery-upload-card .panel-section-title {
      margin-bottom: 8px;
      font-size: 1rem;
    }

    .media-delivery-upload-card .panel-stack {
      margin-bottom: 0;
    }

    .media-delivery-files-grid {
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      margin-top: 12px;
      gap: 12px;
    }

    .media-file-list-card {
      margin: 0;
      border: 1px solid #d8e2ef;
      background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .media-file-list {
      display: grid;
      gap: 10px;
    }

    .media-file-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 12px;
      border: 1px solid #e1e9f4;
      border-radius: 10px;
      background: #fff;
    }

    .media-file-meta {
      min-width: 0;
      display: grid;
      gap: 4px;
    }

    .media-file-kind {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 11px;
      letter-spacing: 0.04em;
      font-weight: 700;
      text-transform: uppercase;
      color: #28415f;
      background: #eef4fb;
      border: 1px solid #d0dced;
    }

    .media-file-name {
      color: #1e3450;
      font-weight: 600;
      word-break: break-word;
    }

    .media-file-actions {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: nowrap;
    }

    .media-file-list-cta {
      margin-top: 4px;
      justify-content: flex-end;
    }

    .media-file-list-cta-group {
      justify-content: flex-end;
      gap: 8px;
      flex-wrap: wrap;
    }

    .media-file-row.is-hidden-by-default {
      display: none;
    }

    @media (max-width: 960px) {
      .media-delivery-upload-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .media-file-row {
        flex-direction: column;
        align-items: stretch;
      }

      .media-file-actions {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
      }
    }
  </style>
</head>
<body class="panel-page<?php echo e(auth()->check() ? (in_array(strtolower((string) auth()->user()->role), ['admin', 'owner', 'manager', 'photographer', 'editor'], true) ? ' panel-page-admin' : ' panel-page-client') : ''); ?>">
  <?php if(auth()->guard()->check()): ?>
    <?php
      $panelRole = strtolower((string) auth()->user()->role);
      $isInternalRole = in_array($panelRole, ['admin', 'owner', 'manager', 'photographer', 'editor'], true);
      $canManageUsers = in_array($panelRole, ['owner', 'admin'], true);
      $canManagePipeline = in_array($panelRole, ['owner', 'admin', 'manager'], true);
      $accessLabel = match ($panelRole) {
        'owner' => 'Owner Access',
        'admin' => 'Admin Access',
        'manager' => 'Manager Access',
        'photographer' => 'Photographer Access',
        'editor' => 'Editor Access',
        
        'client' => 'Client Access',
        default => 'User Access',
      };
    ?>
  <?php endif; ?>

  <div class="panel-app <?php if(auth()->guard()->check()): ?><?php echo e($isInternalRole ? ' panel-app-admin' : ' panel-app-client'); ?><?php endif; ?>" id="panel-app">
    <aside class="panel-sidebar" id="panel-sidebar">
      <div class="panel-brand">
        <div class="panel-brand-logo" aria-label="Maccento CRM brand mark">
          <span class="panel-brand-logo-m">M</span><span class="panel-brand-logo-c">C</span>
        </div>
        <div class="panel-brand-meta">
          <p class="panel-brand-name">Maccento CRM</p>
          <?php if(auth()->guard()->check()): ?>
          <p class="panel-brand-role"><?php echo e(strtoupper((string) auth()->user()->role)); ?></p>
          <?php endif; ?>
        </div>
        <button class="panel-collapse-toggle" type="button" aria-label="Collapse sidebar" data-panel-collapse>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>

      <nav class="panel-nav">
        <?php if(auth()->guard()->check()): ?>
          <?php if($isInternalRole): ?>
          <p class="panel-nav-group-title">Overview</p>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.dashboard')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.dashboard')); ?>" title="Dashboard">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9zm10 7h6v-9h-6v9zM4 20h6v-5H4v5zm10-11h6V4h-6v5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Dashboard</span>
          </a>

          <?php if(in_array($panelRole, ['admin', 'owner', 'manager'], true)): ?>
          <p class="panel-nav-group-title">Lead Management</p>
          <?php
            $leadNavCounts = cache()->remember('panel_lead_nav_counts', now()->addSeconds(30), static function (): array {
              return [
                'all' => \App\Models\LeadProfile::query()->count(),
                'ai' => \App\Models\LeadProfile::query()->whereHas('conversation', function ($query): void {
                  $query->where('channel', 'website_widget');
                })->count(),
                'packages' => \App\Models\LeadProfile::query()->whereHas('conversation', function ($query): void {
                  $query->where('channel', 'package_builder');
                })->count(),
                'submissions' => \App\Models\WebsiteFormSubmission::query()->count(),
              ];
            });
          ?>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.leads.*') && !request()->routeIs('admin.leads.ai.*') && !request()->routeIs('admin.leads.packages.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.leads.index')); ?>" title="All Leads">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">All Leads</span>
            <span class="panel-nav-count"><?php echo e(number_format((int) ($leadNavCounts['all'] ?? 0))); ?></span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.leads.ai.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.leads.ai.index')); ?>" title="Leads from AI Assistance">
            <span class="panel-nav-icon"><img src="<?php echo e(asset('assets/media/icon/ai_icon.png')); ?>" alt="" aria-hidden="true"></span>
            <span class="panel-nav-text">Leads from AI Assistance</span>
            <span class="panel-nav-count"><?php echo e(number_format((int) ($leadNavCounts['ai'] ?? 0))); ?></span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.leads.packages.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.leads.packages.index')); ?>" title="Leads from Packages">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7.5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v4a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-4H5a2 2 0 0 1-2-2v-3zm5 5v4h8v-4H8zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Leads from Packages</span>
            <span class="panel-nav-count"><?php echo e(number_format((int) ($leadNavCounts['packages'] ?? 0))); ?></span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.form-submissions*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.form-submissions')); ?>" title="Website Submissions">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14a2 2 0 0 1 2 2v14l-4-3-4 3-4-3-4 3V5a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
            <span class="panel-nav-text">Website Submissions</span>
            <span class="panel-nav-count"><?php echo e(number_format((int) ($leadNavCounts['submissions'] ?? 0))); ?></span>
          </a>

          <p class="panel-nav-group-title">Sales & Billing</p>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.quotes.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.quotes.index')); ?>" title="Quote Pipeline">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4V5zm3 3v2h10V8H7zm0 4v2h6v-2H7z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Quote Pipeline</span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.invoices.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.invoices.index')); ?>" title="Invoices">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm8 1.5V9h4.5M8 13h8m-8 3h8m-8-6h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Invoices</span>
          </a>

          <?php
            $composeActive = request()->routeIs('admin.emails.inbox') && (string) request()->query('compose') === '1';
            $automationActive = request()->routeIs('admin.emails.automation.*');
            $emailNavCounts = cache()->remember('panel_email_nav_counts', now()->addSeconds(30), static function (): array {
              return [
                'inbox' => \App\Models\InboundEmail::query()->count(),
                'sent' => \App\Models\EmailLog::query()->count(),
                'drafts' => \App\Models\EmailDraft::query()->where('status', 'draft')->count(),
              ];
            });
            $emailNavTotal = (int) (($emailNavCounts['inbox'] ?? 0) + ($emailNavCounts['sent'] ?? 0) + ($emailNavCounts['drafts'] ?? 0));
          ?>
          <div class="panel-nav-link-group <?php if(request()->routeIs('admin.emails.*')): ?> is-active <?php endif; ?>" data-subnav-group="emails">
            <div class="panel-nav-head">
              <a class="panel-nav-link <?php if(request()->routeIs('admin.emails.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.inbox')); ?>" title="Email Center">
                <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6zm2 .5V8l7 4.7L19 8V6.5l-7 4.6-7-4.6z" fill="currentColor"/></svg></span>
                <span class="panel-nav-text">Email Center</span>
                <span class="panel-nav-count"><?php echo e(number_format($emailNavTotal)); ?></span>
              </a>
              <button class="panel-subnav-toggle" type="button" aria-label="Toggle Email Center menu" aria-expanded="true" data-subnav-toggle="emails">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M6 8l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
            <div class="panel-subnav" data-subnav="emails">
              <a class="panel-subnav-link <?php if($composeActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.inbox', ['compose' => 1])); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <span>Compose</span>
              </a>
              <a class="panel-subnav-link <?php if(request()->routeIs('admin.emails.inbox') && !$composeActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.inbox')); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6zm2 .5v1.2l5 3.2 5-3.2V6.5l-5 3.1-5-3.1z" fill="currentColor"/></svg></span>
                <span>Inbox</span>
                <span class="panel-subnav-count"><?php echo e(number_format((int) ($emailNavCounts['inbox'] ?? 0))); ?></span>
              </a>
              <a class="panel-subnav-link <?php if(request()->routeIs('admin.emails.sent')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.sent')); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                <span>Sent</span>
                <span class="panel-subnav-count"><?php echo e(number_format((int) ($emailNavCounts['sent'] ?? 0))); ?></span>
              </a>
              <a class="panel-subnav-link <?php if(request()->routeIs('admin.emails.drafts')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.drafts')); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Drafts</span>
                <span class="panel-subnav-count"><?php echo e(number_format((int) ($emailNavCounts['drafts'] ?? 0))); ?></span>
              </a>
              <a class="panel-subnav-link <?php if($automationActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.emails.automation.index')); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3.5l1.4 2.1 2.4.5-.9 2.3 1.6 1.8-1.9 1.6.3 2.5-2.4.7-1.1 2.2-2.2-1.1-2.2 1.1-1.1-2.2-2.4-.7.3-2.5-1.9-1.6 1.6-1.8-.9-2.3 2.4-.5L10 3.5zm0 4.2a2.3 2.3 0 1 0 0 4.6 2.3 2.3 0 0 0 0-4.6z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg></span>
                <span>Automation</span>
              </a>
            </div>
          </div>
          <?php endif; ?>

          <p class="panel-nav-group-title">Communication</p>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.messages.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.messages.index')); ?>" title="Messages/Chats">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7A2.5 2.5 0 0 1 17.5 14H10l-4.5 4v-4H6.5A2.5 2.5 0 0 1 4 11.5v-5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Messages/Chats</span>
          </a>\r\n          <p class="panel-nav-group-title">Delivery</p>
          <?php
            $projectCreateActive = request()->routeIs('admin.projects.index') && (string) request()->query('project_action') === 'create';
            $projectCompletedActive = request()->routeIs('admin.projects.index') && (string) request()->query('project_scope', 'ongoing') === 'past';
            $projectOngoingActive = request()->routeIs('admin.projects.index') && !$projectCreateActive && !$projectCompletedActive;
          ?>
          <div class="panel-nav-link-group <?php if(request()->routeIs('admin.projects.index')): ?> is-active <?php endif; ?>" data-subnav-group="projects">
            <div class="panel-nav-head">
              <a class="panel-nav-link <?php if(request()->routeIs('admin.projects.index')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.projects.index')); ?>" title="Projects">
                <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h7l2 2h3a2 2 0 0 1 2 2v2H4V5zm0 5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9zm4 3h8m-8 3h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="panel-nav-text">Projects</span>
              </a>
              <button class="panel-subnav-toggle" type="button" aria-label="Toggle Projects menu" aria-expanded="true" data-subnav-toggle="projects">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M6 8l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
            <div class="panel-subnav" data-subnav="projects">
              <?php if($canManagePipeline): ?>
              <a class="panel-subnav-link panel-subnav-link-project <?php if($projectCreateActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.projects.index', ['project_action' => 'create', 'project_scope' => 'all', 'project_view' => 'table'])); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4.2 3.5h11.6a1.7 1.7 0 0 1 1.7 1.7v9.6a1.7 1.7 0 0 1-1.7 1.7H4.2a1.7 1.7 0 0 1-1.7-1.7V5.2a1.7 1.7 0 0 1 1.7-1.7zm5 2.8v2.5H6.7v2h2.5v2.5h2v-2.5h2.5v-2h-2.5V6.3h-2z" fill="currentColor"/></svg></span>
                <span>Create New Project</span>
              </a>
              <?php endif; ?>
              <a class="panel-subnav-link panel-subnav-link-project <?php if($projectOngoingActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.projects.index', ['project_scope' => 'ongoing', 'project_view' => 'table'])); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2.8a7.2 7.2 0 1 1 0 14.4 7.2 7.2 0 0 1 0-14.4zm-.9 3.3v4.2c0 .3.2.6.4.8l3.1 2.1 1.1-1.6-2.6-1.7V6.1H9.1z" fill="currentColor"/></svg></span>
                <span>On Going Project</span>
              </a>
              <a class="panel-subnav-link panel-subnav-link-project <?php if($projectCompletedActive): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.projects.index', ['project_scope' => 'past', 'project_view' => 'table'])); ?>">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.8" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M6.7 10.2l2.2 2.2 4.6-4.6" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>View Complete Project</span>
              </a>
            </div>
          </div>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.media-delivery.*') && !request()->routeIs('admin.media-delivery.watermark.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.media-delivery.index')); ?>" title="Media Delivery">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3H4V6zm0 5h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7zm3 2h10m-6 3h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Media Delivery</span>
          </a>
          <?php if($canManagePipeline): ?>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.media-delivery.watermark.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.media-delivery.watermark.index')); ?>" title="Watermark Settings">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Watermark Settings</span>
          </a>
          <?php endif; ?>

          <p class="panel-nav-group-title">Accounts</p>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.clients.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.clients.index')); ?>" title="Clients">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.57 2.99-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11zM8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V20h7v-3.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Clients</span>
          </a>
          <?php if($canManageUsers): ?>
          <a class="panel-nav-link <?php if(request()->routeIs('admin.users.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('admin.users.index')); ?>" title="User Accounts">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm-7 8a7 7 0 0 1 14 0H5zm12.5-9.5h4m-2-2v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="panel-nav-text">User Accounts</span>
          </a>
          <?php endif; ?>
          <?php else: ?>
          <p class="panel-nav-group-title">Client Workspace</p>
          <a class="panel-nav-link <?php if(request()->routeIs('user.dashboard')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.dashboard')); ?>" title="My Dashboard">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9zm0 4a2.5 2.5 0 1 1-2.5 2.5A2.5 2.5 0 0 1 12 7zm0 11a6 6 0 0 1-4.85-2.45 4.8 4.8 0 0 1 9.7 0A6 6 0 0 1 12 18z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Overview</span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('user.projects.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.projects.index')); ?>" title="Projects">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11zm4 1.5h7m-7 4h7m-7 4h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="panel-nav-text">Projects</span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('user.deliveries.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.deliveries.index')); ?>" title="Deliveries">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7zm3-3h8l1 3H7l1-3zm1 8h6m-3-3v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Media & Deliveries</span>
          </a>

          <p class="panel-nav-group-title">Billing</p>
          <a class="panel-nav-link <?php if(request()->routeIs('user.invoices.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.invoices.index')); ?>" title="Invoices">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18l-3-2-2 2-2-2-3 2V3zm3 5h4m-4 4h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Invoices</span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('user.quotes.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.quotes.index')); ?>" title="Quotes">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16H6V4zm3 4h6m-6 4h6m-6 4h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="panel-nav-text">Quotations</span>
          </a>

          <p class="panel-nav-group-title">Communication</p>
          <a class="panel-nav-link <?php if(request()->routeIs('user.messages.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.messages.index')); ?>" title="Messages">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H10l-4 4v-4H7.5A2.5 2.5 0 0 1 5 12.5v-6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Messages</span>
          </a>
          <a class="panel-nav-link <?php if(request()->routeIs('user.account.*')): ?> is-active <?php endif; ?>" href="<?php echo e(route('user.account.index')); ?>" title="Account">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="panel-nav-text">Account</span>
          </a>
          <?php endif; ?>
        <?php endif; ?>
        <p class="panel-nav-group-title">Website</p>
        <a class="panel-nav-link" href="<?php echo e(route('home')); ?>" title="Public Website">
          <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l9 7h-3v11h-5v-6H11v6H6V9H3l9-7z" fill="currentColor"/></svg></span>
          <span class="panel-nav-text">Public Website</span>
        </a>
      </nav>

      <?php if(auth()->guard()->check()): ?>
      <div class="panel-sidebar-foot">
        <form action="<?php echo e(route('logout')); ?>" method="post" class="panel-sidebar-logout-form">
          <?php echo csrf_field(); ?>
          <button class="panel-btn panel-btn-primary panel-sidebar-logout" type="submit" title="Log out">
            <span class="panel-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M12 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span>Log out</span>
          </button>
        </form>
      </div>
      <?php endif; ?>
    </aside>
    <button class="panel-sidebar-overlay" type="button" aria-label="Close sidebar" data-panel-overlay></button>

    <div class="panel-main">
      <main class="panel-shell">
        <header class="panel-topbar">
          <div class="panel-topbar-left">
            <button class="panel-mobile-toggle" type="button" aria-label="Toggle sidebar" aria-controls="panel-sidebar" aria-expanded="false" data-panel-toggle>
              <span></span><span></span><span></span>
            </button>
            <div>
              <h1 class="panel-title"><?php echo e($heading ?? 'Dashboard'); ?></h1>
              <?php if(!empty($subheading)): ?>
              <p class="panel-sub"><?php echo e($subheading); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <?php if(auth()->guard()->check()): ?>
          <div class="panel-actions">
            <div class="panel-notify" data-panel-notify data-feed-url="<?php echo e(route('notifications.feed')); ?>" data-read-all-url="<?php echo e(route('notifications.read-all-ajax')); ?>" data-read-url-template="<?php echo e(url('/notifications/__ID__/read-ajax')); ?>" data-csrf="<?php echo e(csrf_token()); ?>">
              <button class="panel-notify-btn" type="button" aria-expanded="false" data-panel-notify-toggle title="Notifications">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a5 5 0 0 0-5 5v2.3c0 .8-.3 1.57-.84 2.14L5 13.73V15h14v-1.27l-1.16-1.3A3 3 0 0 1 17 10.3V8a5 5 0 0 0-5-5zm0 18a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 21z" fill="currentColor"/></svg>
                <?php if(($panelUnreadNotifications ?? 0) > 0): ?>
                <span class="panel-notify-count"><?php echo e($panelUnreadNotifications); ?></span>
                <?php endif; ?>
              </button>
              <div class="panel-notify-menu" data-panel-notify-menu hidden>
                <div class="panel-notify-head">
                  <strong>Notifications</strong>
                  <button class="panel-link" type="button" data-notify-mark-all <?php if(($panelUnreadNotifications ?? 0) === 0): ?> hidden <?php endif; ?>>Mark all read</button>
                </div>
                <div class="panel-notify-filters">
                  <button class="panel-notify-filter is-active" type="button" data-notify-filter="all">All</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="quotes">Quotes</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="invoices">Invoices</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="messages">Messages</button>
                </div>
                <div class="panel-notify-list">
                  <?php
                    $notifyCategoryMap = [
                      'new_quote_submission' => 'quotes',
                      'quote_status_updated' => 'quotes',
                      'quote_revision_requested' => 'quotes',
                      'invoice_created' => 'invoices',
                      'invoice_status_updated' => 'invoices',
                      'new_admin_message' => 'messages',
                      'new_service_request' => 'messages',
                      'service_request_status_updated' => 'messages',
                      'project_status_updated' => 'messages',
                    ];
                  ?>
                  <?php $__empty_1 = true; $__currentLoopData = ($panelNotifications ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <div class="panel-notify-item <?php echo e($notification->read_at ? '' : 'is-unread'); ?>" data-notify-category="<?php echo e($notifyCategoryMap[$notification->type] ?? 'other'); ?>">
                    <div class="panel-notify-copy">
                      <p class="panel-notify-title"><?php echo e($notification->title); ?></p>
                      <?php if($notification->body): ?>
                      <p class="panel-notify-body"><?php echo e($notification->body); ?></p>
                      <?php endif; ?>
                      <p class="panel-notify-time"><?php echo e($notification->created_at?->diffForHumans()); ?></p>
                    </div>
                    <div class="panel-notify-actions">
                      <?php if($notification->action_url): ?>
                      <a class="panel-link" href="<?php echo e($notification->action_url); ?>">Open</a>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <p class="panel-muted">No notifications yet.</p>
                  <?php endif; ?>
                  <p class="panel-muted" data-notify-empty hidden>No notifications in this category.</p>
                </div>
              </div>
            </div>
            <form action="<?php echo e(route('logout')); ?>" method="post">
              <?php echo csrf_field(); ?>
              <button class="panel-btn panel-btn-primary" type="submit">Log out</button>
            </form>
          </div>
          <?php endif; ?>
        </header>

        <?php if(session('status')): ?>
        <section class="panel-card"><span class="panel-badge"><?php echo e(session('status')); ?></span></section>
        <?php endif; ?>

        <?php if($errors->any()): ?>
        <section class="panel-card"><span class="panel-badge panel-badge-danger"><?php echo e($errors->first()); ?></span></section>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
      </main>
    </div>

    <?php if(auth()->guard()->check()): ?>
      <?php if($isInternalRole): ?>
      <button
        class="panel-assistant-launch"
        type="button"
        data-admin-assistant-launch
        aria-controls="panel-admin-assistant"
        aria-expanded="false"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10h10M7 14h7m-9 7 2.1-3.2A8.5 8.5 0 1 1 20.5 12 8.4 8.4 0 0 1 12 20a8.2 8.2 0 0 1-2.3-.3L5 21z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="panel-assistant-launch-text">
          <span class="panel-assistant-launch-title">Admin Assistant</span>
          <span class="panel-assistant-launch-sub">Real-time CRM help</span>
        </span>
      </button>

      <section
        class="panel-assistant"
        id="panel-admin-assistant"
        data-admin-assistant
        data-session-url="<?php echo e(route('admin.assistant.session')); ?>"
        data-message-url="<?php echo e(route('admin.assistant.message')); ?>"
        data-csrf="<?php echo e(csrf_token()); ?>"
        hidden
      >
        <div class="panel-assistant-head">
          <div class="panel-assistant-head-main">
            <div class="panel-assistant-head-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M7 10h10M7 14h7m-9 7 2.1-3.2A8.5 8.5 0 1 1 20.5 12 8.4 8.4 0 0 1 12 20a8.2 8.2 0 0 1-2.3-.3L5 21z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="panel-assistant-head-copy">
              <span class="panel-assistant-badge">Internal CRM Help</span>
              <h2 class="panel-assistant-title">Admin Assistant</h2>
              <p class="panel-assistant-sub">Workflow guidance, CRM navigation, and operational answers for internal staff.</p>
            </div>
          </div>
          <button class="panel-assistant-close" type="button" aria-label="Close assistant" data-admin-assistant-close>&times;</button>
        </div>

        <div class="panel-assistant-body" data-admin-assistant-body>
          <div class="panel-assistant-stack" data-admin-assistant-messages>
            <p class="panel-assistant-empty">Loading assistant session...</p>
          </div>
        </div>

        <div class="panel-assistant-foot">
          <div class="panel-assistant-meta">
            <span class="panel-assistant-status" data-admin-assistant-status></span>
          </div>
          <form class="panel-assistant-form" data-admin-assistant-form>
            <input
              class="panel-assistant-input"
              type="text"
              name="message"
              placeholder="Type your message..."
              maxlength="1800"
              required
              data-admin-assistant-input
            >
            <button class="panel-btn panel-btn-primary panel-assistant-send" type="submit" data-admin-assistant-send>Send</button>
          </form>
        </div>
      </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <script>
    (function () {
      const app = document.getElementById('panel-app');
      const topbar = document.querySelector('.panel-topbar');
      const mobileToggle = document.querySelector('[data-panel-toggle]');
      const collapseToggle = document.querySelector('[data-panel-collapse]');
      const sidebarOverlay = document.querySelector('[data-panel-overlay]');
      if (!app) return;

      const syncTopbarState = function () {
        if (!topbar) return;
        const condensed = window.scrollY > 20;
        topbar.classList.toggle('is-condensed', condensed);
      };

      syncTopbarState();
      window.addEventListener('scroll', syncTopbarState, { passive: true });

      const storageKey = 'maccento_panel_sidebar_collapsed';
      const media = window.matchMedia('(max-width: 1100px)');
      const setSidebarOpen = function (open) {
        app.classList.toggle('sidebar-open', open);
        document.body.classList.toggle('panel-mobile-nav-open', media.matches && open);
        if (mobileToggle) {
          mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
      };

      const applyStoredState = function () {
        if (media.matches) return;
        const collapsed = localStorage.getItem(storageKey) === '1';
        app.classList.toggle('sidebar-collapsed', collapsed);
      };

      applyStoredState();

      if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
          setSidebarOpen(!app.classList.contains('sidebar-open'));
        });
      }

      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
          setSidebarOpen(false);
        });
      }

      document.querySelectorAll('.panel-sidebar .panel-nav-link, .panel-sidebar .panel-subnav-link').forEach(function (link) {
        link.addEventListener('click', function () {
          if (!media.matches) return;
          setSidebarOpen(false);
        });
      });

      if (collapseToggle) {
        collapseToggle.addEventListener('click', function () {
          if (media.matches) return;
          const collapsed = app.classList.toggle('sidebar-collapsed');
          localStorage.setItem(storageKey, collapsed ? '1' : '0');
        });
      }

      media.addEventListener('change', function () {
        if (media.matches) {
          app.classList.remove('sidebar-collapsed');
          document.body.classList.remove('panel-mobile-nav-open');
          return;
        }
        setSidebarOpen(false);
        applyStoredState();
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && app.classList.contains('sidebar-open')) {
          setSidebarOpen(false);
        }
      });

      document.querySelectorAll('.panel-table').forEach(function (table) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(function (header) {
          return String(header.textContent || '').trim();
        });

        if (headers.length === 0) {
          return;
        }

        table.querySelectorAll('tbody tr').forEach(function (row) {
          Array.from(row.children).forEach(function (cell, index) {
            if (!(cell instanceof HTMLElement)) {
              return;
            }

            if (!cell.hasAttribute('data-label')) {
              cell.setAttribute('data-label', headers[index] || 'Detail');
            }
          });
        });
      });

      const subnavGroups = Array.from(document.querySelectorAll('[data-subnav-group]'));
      subnavGroups.forEach(function (group) {
        const key = String(group.getAttribute('data-subnav-group') || '').trim();
        if (key === '') return;

        const toggle = group.querySelector('[data-subnav-toggle="' + key + '"]');
        const submenu = group.querySelector('[data-subnav="' + key + '"]');
        if (!toggle || !submenu) return;

        const stateKey = 'maccento_panel_subnav_collapsed_' + key;
        const apply = function (collapsed) {
          group.classList.toggle('is-collapsed', collapsed);
          toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        };

        const stored = localStorage.getItem(stateKey);
        const defaultCollapsed = !group.classList.contains('is-active');
        apply(stored === null ? defaultCollapsed : stored === '1');

        toggle.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          const collapsed = !group.classList.contains('is-collapsed');
          apply(collapsed);
          localStorage.setItem(stateKey, collapsed ? '1' : '0');
        });
      });

      const notifyWrap = document.querySelector('[data-panel-notify]');
      const notifyToggle = document.querySelector('[data-panel-notify-toggle]');
      const notifyMenu = document.querySelector('[data-panel-notify-menu]');
      if (notifyWrap && notifyToggle && notifyMenu) {
        const filterButtons = notifyMenu.querySelectorAll('[data-notify-filter]');
        const filteredEmpty = notifyMenu.querySelector('[data-notify-empty]');
        const listEl = notifyMenu.querySelector('.panel-notify-list');
        const markAllBtn = notifyMenu.querySelector('[data-notify-mark-all]');
        const feedUrl = notifyWrap.getAttribute('data-feed-url') || '';
        const readAllUrl = notifyWrap.getAttribute('data-read-all-url') || '';
        const readUrlTemplate = notifyWrap.getAttribute('data-read-url-template') || '';
        const csrfToken = notifyWrap.getAttribute('data-csrf') || '';
        const categoryMap = {
          new_quote_submission: 'quotes',
          quote_status_updated: 'quotes',
          quote_revision_requested: 'quotes',
          invoice_created: 'invoices',
          invoice_status_updated: 'invoices',
          new_admin_message: 'messages',
          new_service_request: 'messages',
          service_request_status_updated: 'messages',
          project_status_updated: 'messages'
        };

        let currentFilter = 'all';
        let notifications = [];
        let isFetching = false;

        const escapeHtml = function (value) {
          return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        };

        const updateUnreadBadge = function (count) {
          let badge = notifyToggle.querySelector('.panel-notify-count');
          if (count > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'panel-notify-count';
              notifyToggle.appendChild(badge);
            }
            badge.textContent = String(count);
          } else if (badge) {
            badge.remove();
          }

          if (markAllBtn) {
            markAllBtn.hidden = count <= 0;
          }
        };

        const buildNotificationItem = function (item) {
          const isUnread = Boolean(item && item.is_unread);
          const category = categoryMap[String(item.type || '')] || 'other';
          const actionUrl = String(item.action_url || '');
          const title = escapeHtml(item.title || 'Notification');
          const body = escapeHtml(item.body || '');
          const time = escapeHtml(item.created_human || '');

          const wrapper = document.createElement('div');
          wrapper.className = 'panel-notify-item' + (isUnread ? ' is-unread' : '');
          wrapper.setAttribute('data-notify-category', category);
          wrapper.setAttribute('data-notify-id', String(item.id || ''));

          let actionsHtml = '';
          if (actionUrl !== '') {
            actionsHtml = '<div class="panel-notify-actions"><a class="panel-link" href="' + escapeHtml(actionUrl) + '" data-notify-open="1">Open</a></div>';
          }

          wrapper.innerHTML = '' +
            '<div class="panel-notify-copy">' +
              '<p class="panel-notify-title">' + title + '</p>' +
              (body !== '' ? '<p class="panel-notify-body">' + body + '</p>' : '') +
              (time !== '' ? '<p class="panel-notify-time">' + time + '</p>' : '') +
            '</div>' +
            actionsHtml;

          return wrapper;
        };

        const renderNotifications = function () {
          if (!listEl) return;

          const staticEmpty = listEl.querySelector('[data-notify-empty]');
          listEl.querySelectorAll('[data-notify-category], .panel-muted:not([data-notify-empty])').forEach(function (el) {
            el.remove();
          });

          if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'panel-muted';
            empty.textContent = 'No notifications yet.';
            listEl.insertBefore(empty, staticEmpty || null);
          } else {
            notifications.forEach(function (item) {
              const row = buildNotificationItem(item);
              listEl.insertBefore(row, staticEmpty || null);
            });
          }

          applyNotifyFilter(currentFilter);
        };

        const applyNotifyFilter = function (filterKey) {
          const items = notifyMenu.querySelectorAll('[data-notify-category]');
          let visible = 0;
          items.forEach(function (item) {
            const category = item.getAttribute('data-notify-category') || 'other';
            const show = filterKey === 'all' || category === filterKey;
            item.classList.toggle('is-hidden', !show);
            if (show) visible += 1;
          });
          if (filteredEmpty) {
            filteredEmpty.classList.toggle('is-hidden', visible !== 0);
          }
          filterButtons.forEach(function (button) {
            const active = button.getAttribute('data-notify-filter') === filterKey;
            button.classList.toggle('is-active', active);
          });
          currentFilter = filterKey;
        };

        const fetchFeed = function () {
          if (!feedUrl || isFetching) {
            return;
          }

          isFetching = true;
          fetch(feedUrl, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Feed request failed');
              }
              return response.json();
            })
            .then(function (data) {
              notifications = Array.isArray(data && data.notifications) ? data.notifications : [];
              renderNotifications();
              updateUnreadBadge(Number(data && data.unread_count ? data.unread_count : 0));
            })
            .catch(function () {
            })
            .finally(function () {
              isFetching = false;
            });
        };

        const markRead = function (notificationId, onDone) {
          const id = String(notificationId || '').trim();
          if (id === '' || !readUrlTemplate || !csrfToken) {
            if (typeof onDone === 'function') onDone();
            return;
          }

          const url = readUrlTemplate.replace('__ID__', encodeURIComponent(id));
          fetch(url, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({})
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Read request failed');
              }
              return response.json();
            })
            .then(function (data) {
              const index = notifications.findIndex(function (row) { return String(row.id) === id; });
              if (index >= 0) {
                notifications[index].is_unread = false;
              }
              renderNotifications();
              updateUnreadBadge(Number(data && data.unread_count ? data.unread_count : 0));
            })
            .catch(function () {
            })
            .finally(function () {
              if (typeof onDone === 'function') onDone();
            });
        };

        if (markAllBtn) {
          markAllBtn.addEventListener('click', function () {
            if (!readAllUrl || !csrfToken) {
              return;
            }

            fetch(readAllUrl, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin',
              body: JSON.stringify({})
            })
              .then(function (response) {
                if (!response.ok) {
                  throw new Error('Read all request failed');
                }
                return response.json();
              })
              .then(function () {
                notifications = notifications.map(function (row) {
                  row.is_unread = false;
                  return row;
                });
                renderNotifications();
                updateUnreadBadge(0);
              })
              .catch(function () {
              });
          });
        }

        notifyMenu.addEventListener('click', function (event) {
          const openLink = event.target.closest('[data-notify-open]');
          if (openLink) {
            const row = openLink.closest('[data-notify-id]');
            const notifyId = row ? row.getAttribute('data-notify-id') : '';
            if (notifyId) {
              event.preventDefault();
              const href = openLink.getAttribute('href') || '';
              markRead(notifyId, function () {
                if (href !== '') {
                  window.location.href = href;
                }
              });
            }
            return;
          }

          const row = event.target.closest('[data-notify-id]');
          if (!row) {
            return;
          }

          if (event.target.closest('a,button,form')) {
            return;
          }

          const notifyId = row.getAttribute('data-notify-id') || '';
          if (notifyId && row.classList.contains('is-unread')) {
            markRead(notifyId);
          }
        });

        filterButtons.forEach(function (button) {
          button.addEventListener('click', function () {
            applyNotifyFilter(button.getAttribute('data-notify-filter') || 'all');
          });
        });

        notifyToggle.addEventListener('click', function () {
          const open = notifyMenu.hidden;
          notifyMenu.hidden = !open;
          notifyToggle.setAttribute('aria-expanded', String(open));
          if (open) {
            fetchFeed();
            applyNotifyFilter(currentFilter || 'all');
          }
        });
        document.addEventListener('click', function (event) {
          if (!notifyWrap.contains(event.target)) {
            notifyMenu.hidden = true;
            notifyToggle.setAttribute('aria-expanded', 'false');
          }
        });

        fetchFeed();
        window.setInterval(fetchFeed, 15000);
      }

      const assistantLaunch = document.querySelector('[data-admin-assistant-launch]');
      const assistantPanel = document.querySelector('[data-admin-assistant]');
      if (assistantLaunch && assistantPanel) {
        const assistantClose = assistantPanel.querySelector('[data-admin-assistant-close]');
        const assistantBody = assistantPanel.querySelector('[data-admin-assistant-body]');
        const assistantMessages = assistantPanel.querySelector('[data-admin-assistant-messages]');
        const assistantForm = assistantPanel.querySelector('[data-admin-assistant-form]');
        const assistantInput = assistantPanel.querySelector('[data-admin-assistant-input]');
        const assistantSend = assistantPanel.querySelector('[data-admin-assistant-send]');
        const assistantStatus = assistantPanel.querySelector('[data-admin-assistant-status]');
        const sessionUrl = assistantPanel.getAttribute('data-session-url') || '';
        const messageUrl = assistantPanel.getAttribute('data-message-url') || '';
        const csrfToken = assistantPanel.getAttribute('data-csrf') || '';
        let conversationId = '';
        let sessionLoaded = false;

        const setAssistantOpen = function (open) {
          assistantPanel.hidden = !open;
          assistantPanel.classList.toggle('is-open', open);
          assistantLaunch.setAttribute('aria-expanded', open ? 'true' : 'false');
          if (open) {
            window.setTimeout(function () {
              if (assistantInput) {
                assistantInput.focus();
              }
            }, 60);
          }
        };

        const scrollAssistantToBottom = function () {
          if (!assistantBody) return;
          assistantBody.scrollTop = assistantBody.scrollHeight;
        };

        const renderAssistantMessages = function (items) {
          if (!assistantMessages) return;
          assistantMessages.innerHTML = '';

          if (!Array.isArray(items) || items.length === 0) {
            assistantMessages.innerHTML = '<p class="panel-assistant-empty">Ask a CRM question to start the assistant.</p>';
            return;
          }

          items.forEach(function (item) {
            const role = String(item && item.role ? item.role : 'assistant');
            const bubble = document.createElement('div');
            bubble.className = 'panel-assistant-msg ' + (role === 'user' ? 'is-user' : 'is-assistant');
            bubble.textContent = String(item && item.content ? item.content : '');
            assistantMessages.appendChild(bubble);
          });

          scrollAssistantToBottom();
        };

        const appendAssistantMessage = function (role, content) {
          if (!assistantMessages) return;
          const empty = assistantMessages.querySelector('.panel-assistant-empty');
          if (empty) {
            empty.remove();
          }
          const bubble = document.createElement('div');
          bubble.className = 'panel-assistant-msg ' + (role === 'user' ? 'is-user' : 'is-assistant');
          bubble.textContent = content;
          assistantMessages.appendChild(bubble);
          scrollAssistantToBottom();
        };

        const setAssistantBusy = function (busy, message) {
          if (assistantSend) {
            assistantSend.disabled = busy;
          }
          if (assistantInput) {
            assistantInput.disabled = busy;
          }
          if (assistantStatus) {
            assistantStatus.textContent = message || '';
          }
        };

        const loadAssistantSession = async function () {
          if (sessionLoaded || sessionUrl === '') return;
          setAssistantBusy(true, 'Loading...');

          try {
            const response = await fetch(sessionUrl, {
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
              throw new Error('Assistant session could not be loaded.');
            }

            conversationId = String(data.conversation_id || '');
            renderAssistantMessages(Array.isArray(data.messages) ? data.messages : []);
            sessionLoaded = true;
            setAssistantBusy(false, '');
          } catch (error) {
            if (assistantMessages) {
              assistantMessages.innerHTML = '<p class="panel-assistant-empty">Assistant is unavailable right now. Reload the page and try again.</p>';
            }
            setAssistantBusy(false, 'Unavailable');
          }
        };

        assistantLaunch.addEventListener('click', function () {
          const open = !assistantPanel.classList.contains('is-open');
          setAssistantOpen(open);
          if (open) {
            loadAssistantSession();
          }
        });

        if (assistantClose) {
          assistantClose.addEventListener('click', function () {
            setAssistantOpen(false);
          });
        }

        if (assistantForm && assistantInput) {
          assistantForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const message = assistantInput.value.trim();
            if (message === '' || messageUrl === '') {
              return;
            }

            appendAssistantMessage('user', message);
            assistantInput.value = '';
            setAssistantBusy(true, 'Thinking...');

            try {
              const response = await fetch(messageUrl, {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                  conversation_id: conversationId || null,
                  message: message,
                  page_title: document.title || '',
                  page_heading: (document.querySelector('.panel-title') || {}).textContent || '',
                  current_path: window.location.pathname || ''
                })
              });
              const data = await response.json();

              if (!response.ok || !data.ok || !data.message) {
                throw new Error('Assistant reply failed.');
              }

              conversationId = String(data.conversation_id || conversationId || '');
              appendAssistantMessage('assistant', String(data.message.content || ''));
              setAssistantBusy(false, '');
            } catch (error) {
              appendAssistantMessage('assistant', 'I could not process that right now. Try again in a moment.');
              setAssistantBusy(false, 'Retry available');
            }
          });
        }
      }
    })();
  </script>
  <div class="panel-modal" id="panelConfirmModal" aria-hidden="true">
    <div class="panel-modal-card" role="dialog" aria-modal="true" aria-labelledby="panelConfirmTitle">
      <h3 class="panel-modal-title" id="panelConfirmTitle">Confirm action</h3>
      <p class="panel-modal-body" id="panelConfirmMessage">Are you sure?</p>
      <div class="panel-modal-actions">
        <button class="panel-btn" type="button" data-confirm-cancel>Cancel</button>
        <button class="panel-btn panel-btn-danger" type="button" data-confirm-ok>Confirm</button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const modal = document.getElementById('panelConfirmModal');
      if (!modal) return;
      const titleEl = document.getElementById('panelConfirmTitle');
      const messageEl = document.getElementById('panelConfirmMessage');
      const cancelBtn = modal.querySelector('[data-confirm-cancel]');
      const okBtn = modal.querySelector('[data-confirm-ok]');
      let resolveConfirm = null;

      const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (resolveConfirm) {
          resolveConfirm(false);
          resolveConfirm = null;
        }
      };

      window.panelConfirm = function (message, title) {
        return new Promise((resolve) => {
          resolveConfirm = resolve;
          if (titleEl) titleEl.textContent = title || 'Confirm action';
          if (messageEl) messageEl.textContent = message || 'Are you sure?';
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
        });
      };

      if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
      if (okBtn) okBtn.addEventListener('click', () => {
        if (resolveConfirm) {
          resolveConfirm(true);
          resolveConfirm = null;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
      });

      modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
      });

      document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.getAttribute('data-confirm');
        if (!message) return;
        if (form.dataset.confirming === 'true') return;
        event.preventDefault();
        const title = form.getAttribute('data-confirm-title') || 'Confirm action';
        form.dataset.confirming = 'true';
        const ok = await window.panelConfirm(message, title);
        form.dataset.confirming = 'false';
        if (ok) form.submit();
      });
    })();
  </script>
</body>
</html>














<?php /**PATH /home/asifk/projects/maccento/resources/views/layouts/panel.blade.php ENDPATH**/ ?>