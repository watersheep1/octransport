<?php
if (!isset($page_title)) {
    $page_title = 'Admin Dashboard';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --forest: #0b6f4b;
      --forest-dark: #075e3f;
      --sage: #74a656;
      --cream: #f7f2ec;
      --mint: #eff8f3;
      --ink: #123121;
    }

    html, body {
      height: 100%;
    }

    body {
      background: var(--forest);
      font-family: "Segoe UI", Tahoma, sans-serif;
      color: var(--ink);
      overflow: hidden;
    }
    .app-shell {
      height: 100vh;
      display: flex;
      overflow: hidden;
    }
    .sidebar {
      width: 260px;
      min-width: 260px;
      max-width: 260px;
      flex: 0 0 260px;
      box-sizing: border-box;
      background: linear-gradient(180deg, #dff0e3 0%, #cfe5d6 100%);
      color: #1f3d2d;
      padding: 22px 18px;
      border-right: 4px solid rgba(7, 94, 63, 0.12);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      height: 100vh;
      position: sticky;
      top: 0;
    }
    .sidebar .brand {
      font-weight: 700;
      letter-spacing: 0.6px;
      color: #1f3d2d;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      min-width: 0;
    }
    .brand-logo {
      width: 75px;
      height: 66px;
      border-radius: 12px;
      overflow: hidden;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: transparent;
    }
    .brand-logo img {
      width: 150px;
      height: 120px;
      object-fit: contain;
      mix-blend-mode: multiply;
      filter: brightness(1.1);
    }
    .brand-sub {
      color: #557264;
      font-size: 12px;
      font-weight: 500;
    }
    .sidebar .nav-link {
      color: #1f3d2d;
      border-radius: 14px;
      padding: 10px 12px;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      box-sizing: border-box;
      min-width: 0;
    }
    .sidebar .nav-link span:last-child {
      min-width: 0;
      flex: 1 1 auto;
    }
    .nav-icon svg {
      width: 20px;
      height: 20px;
      stroke: currentColor;
      fill: none;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #1e6b4d;
      color: #ffffff;
    }
    .sidebar-sep {
      height: 1px;
      background: rgba(31, 61, 45, 0.2);
      margin: 16px 0;
    }
    .sidebar-profile {
      margin-top: auto;
      padding-top: 16px;
      border-top: 1px solid rgba(31, 61, 45, 0.2);
      width: 100%;
      min-width: 0;
    }
    .profile-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
      width: 100%;
      min-width: 0;
    }
    .profile-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #1e6b4d;
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }
    .logout-link {
      color: #b91c1c;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      padding: 8px 10px;
      border-radius: 12px;
    }
    .content {
      flex: 1;
      padding: 24px;
      min-width: 0;
      height: 100vh;
      overflow-y: auto;
      overflow-x: hidden;
    }
    .topbar {
      background: var(--cream);
      border-radius: 22px;
      padding: 14px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 6px 20px rgba(7, 94, 63, 0.18);
    }
    .search-bar {
      width: 320px;
      background: #efe7dc;
      border-radius: 999px;
      border: none;
    }
    .card-shadow {
      box-shadow: 0 10px 20px rgba(7, 94, 63, 0.2);
      border: none;
      background: var(--cream);
      border-radius: 18px;
    }
    .stat-card h2 {
      margin: 0;
      font-size: 28px;
      font-weight: 700;
    }
    .badge-pill {
      background: #dce9d5;
      color: #1f5c3c;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
    }
    .btn-success {
      background: var(--forest-dark);
      border-color: var(--forest-dark);
    }

    .calendar-cell {
      cursor: pointer;
      background: #ffffff;
    }

    .calendar-cell:hover {
      background: #f4f8f6;
    }

    .calendar-entry {
      background: #e6f4eb;
      border-radius: 6px;
      padding: 4px 6px;
      margin-top: 4px;
      font-size: 12px;
    }

    .calendar-entry small {
      display: block;
      color: #355343;
    }

    .calendar-entry .remove-entry {
      border: none;
      background: transparent;
      color: #b91c1c;
      font-weight: 700;
      cursor: pointer;
      font-size: 11px;
      padding: 0;
      margin-top: 4px;
    }


    .agenda-details {
      background: #e6f4eb;
      border-radius: 12px;
      padding: 12px 14px;
      margin-top: 12px;
      border: 1px solid rgba(31, 92, 60, 0.2);
    }

    .agenda-details h6 {
      margin: 0 0 6px 0;
      font-weight: 700;
    }

    .settings-header h2 {
      margin: 0;
      font-size: 26px;
    }

    .settings-header p {
      margin: 6px 0 0 0;
      color: #6b7a6f;
    }

    .tabs {
      background: #efe7dc;
      border-radius: 12px;
      padding: 6px;
      display: inline-flex;
      gap: 6px;
    }

    .tab {
      border: none;
      background: transparent;
      padding: 8px 14px;
      border-radius: 10px;
      font-weight: 600;
      color: #4b5a51;
      font-size: 14px;
    }

    .tab.active {
      background: #ffffff;
      color: #213629;
      box-shadow: 0 4px 10px rgba(7, 94, 63, 0.12);
    }

    .profile-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 8px 20px rgba(7, 94, 63, 0.12);
      border: 1px solid rgba(7, 94, 63, 0.08);
    }

    .avatar-circle {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #f4dcdc;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: #a53b3b;
    }

    .soft-input {
      background: #f5f0ea;
      border: 1px solid #e2d9cf;
    }

    .soft-input:focus {
      border-color: #b9cbb7;
      box-shadow: 0 0 0 0.2rem rgba(31, 92, 60, 0.15);
    }

    textarea.soft-input {
      resize: none;
    }

    .role-pill {
      background: #def7e6;
      color: #1f5c3c;
      border-radius: 999px;
      padding: 6px 12px;
      font-weight: 600;
      font-size: 12px;
      display: inline-block;
    }

    .icon-btn {
      border: none;
      background: #cf2c1d;
      color: #fff;
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .users-header h2 {
      margin: 0;
      font-size: 28px;
      color: #f2fbf5;
    }

    .users-header p {
      margin: 6px 0 0 0;
      color: #d8ede0;
    }

    .users-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 8px 20px rgba(7, 94, 63, 0.12);
      border: 1px solid rgba(7, 94, 63, 0.08);
    }

    .role-badge {
      background: #e9edf2;
      color: #2c3e50;
      border-radius: 999px;
      padding: 6px 12px;
      font-weight: 600;
      font-size: 12px;
      display: inline-block;
    }

    .action-icon {
      border: none;
      background: transparent;
      color: #5f6b7a;
      font-size: 16px;
      padding: 4px 6px;
    }

    .action-icon.delete {
      color: #cf2c1d;
    }

    .blue-btn {
      background: #1e63e9;
      border-color: #1e63e9;
      color: #fff;
      border-radius: 14px;
      padding: 10px 16px;
      font-weight: 600;
    }

    .soft-modal .modal-content {
      border-radius: 18px;
      border: 1px solid #e3e7ec;
    }

    .soft-modal .form-control,
    .soft-modal .form-select {
      background: #f6f7f9;
      border: 1px solid #e2e6ec;
      border-radius: 12px;
    }

    .approvals-header h2 {
      margin: 0;
      font-size: 28px;
      color: #f2fbf5;
    }

    .approvals-header p {
      margin: 6px 0 0 0;
      color: #d8ede0;
    }

    .approvals-filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      flex: 1 1 auto;
      min-width: 0;
    }

    .approvals-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }

    .approvals-toolbar-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 12px;
      flex-wrap: wrap;
    }

    .approvals-archive-link {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-height: 44px;
      padding: 0 18px;
      border-radius: 999px;
      border: 1px solid rgba(46, 122, 76, 0.14);
      background: #fffdfa;
      color: #1f5c3c;
      font-size: 13px;
      font-weight: 800;
      text-decoration: none;
      box-shadow: 0 8px 18px rgba(14, 82, 48, 0.08);
      transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .approvals-archive-link:hover {
      transform: translateY(-1px);
      background: #f7fbf8;
      color: #184c32;
    }

    .approvals-archive-link svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .approvals-search {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: min(100%, 300px);
      max-width: 330px;
      padding: 0 14px;
      border-radius: 999px;
      border: 1px solid rgba(46, 122, 76, 0.14);
      background: #fffdfa;
      box-shadow: 0 8px 18px rgba(14, 82, 48, 0.08);
      color: #5e7265;
    }

    .approvals-search svg {
      width: 20px;
      height: 20px;
      flex: 0 0 auto;
    }

    .approvals-search-input {
      width: 100%;
      min-height: 44px;
      border: 0;
      background: transparent;
      color: #183123;
      font-size: 13px;
      font-weight: 600;
      outline: none;
      box-shadow: none;
    }

    .approvals-search-input::placeholder {
      color: #8a9990;
    }

    .approvals-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      border: 1px solid rgba(46, 122, 76, 0.14);
      background: rgba(255, 255, 255, 0.12);
      color: #eef8f1;
      border-radius: 999px;
      padding: 12px 18px;
      font-size: 14px;
      font-weight: 800;
      box-shadow: 0 8px 18px rgba(14, 82, 48, 0.08);
      transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }

    .approvals-filter-btn:hover {
      transform: translateY(-1px);
    }

    .approvals-filter-btn.active {
      background: #1f5c3c;
      color: #ffffff;
      box-shadow: 0 12px 22px rgba(14, 82, 48, 0.16);
    }

    .approvals-filter-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 30px;
      height: 30px;
      padding: 0 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.18);
      color: inherit;
      font-size: 13px;
      font-weight: 800;
    }

    .approvals-filter-btn.active .approvals-filter-count {
      background: rgba(255, 255, 255, 0.18);
    }

    .approvals-status-section + .approvals-status-section {
      margin-top: 22px;
    }

    .approvals-section-head {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: 14px;
      margin: 0 0 12px;
      padding: 0 6px;
    }

    .approvals-section-head h3 {
      margin: 0;
      font-size: 22px;
      color: #eef8f1 !important;
    }

    .approvals-section-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 38px;
      height: 38px;
      padding: 0 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.18);
      color: #eef8f1 !important;
      font-size: 14px;
      font-weight: 800;
      margin-left: 2px;
    }

    .approvals-card-shell {
      border-radius: 22px;
      padding: 10px !important;
      background: #fffdfa;
      border: 1px solid rgba(46, 122, 76, 0.12);
      box-shadow: 0 16px 36px rgba(14, 82, 48, 0.12);
      overflow-x: auto;
    }

    .pill {
      border-radius: 999px;
      padding: 6px 14px;
      font-weight: 700;
      font-size: 12px;
      display: inline-block;
    }

    .pill.pending {
      background: #fff3e0;
      color: #f39c12;
    }

    .pill.approved {
      background: #e7f0ff;
      color: #1e63e9;
    }

    .pill.completed {
      background: #e6f7ec;
      color: #1f9d55;
    }

    .pill.cancelled {
      background: #fff0ef;
      color: #c73b34;
    }

    .approvals-table td,
    .approvals-table th {
      vertical-align: middle;
    }

    .approvals-table {
      min-width: 940px;
      margin-bottom: 0;
      background: #ffffff;
      border-radius: 18px;
      overflow: hidden;
      table-layout: fixed;
    }

    .approvals-table th {
      white-space: nowrap;
      padding: 10px 10px;
      font-size: 12px;
      font-weight: 800;
      color: #172c22;
      background: #ffffff;
      border-bottom: 1px solid rgba(18, 49, 33, 0.12);
    }

    .approvals-table td {
      padding: 10px 10px;
      font-size: 12px;
      border-bottom: 1px solid rgba(18, 49, 33, 0.08);
    }

    .approvals-table td strong {
      font-size: 13px;
      font-weight: 800;
      color: #111;
      display: block;
      line-height: 1.25;
      margin-bottom: 2px;
    }

    .approvals-table .small.text-muted {
      font-size: 10px;
      line-height: 1.35;
    }

    .approvals-code-cell strong {
      letter-spacing: -0.02em;
    }

    .approvals-requester-cell strong,
    .approvals-destination-cell strong,
    .approvals-assignment-line,
    .approvals-notes {
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    .approvals-requester-cell {
      width: 160px;
    }

    .approvals-destination-cell {
      width: 120px;
    }

    .approvals-schedule-cell {
      width: 108px;
    }

    .approvals-status-cell {
      width: 116px;
    }

    .approvals-status-subtext {
      margin-top: 6px;
    }

    .approvals-assignment-line {
      font-size: 12px;
      font-weight: 700;
      color: #1a2c21;
      margin-bottom: 2px;
    }

    .approvals-action-cell {
      min-width: 220px;
    }

    .approvals-actions-form {
      display: grid;
      grid-template-columns: minmax(98px, 1fr) minmax(68px, 1fr);
      gap: 5px;
      align-items: center;
      justify-content: end;
      width: 100%;
      max-width: 220px;
      margin-left: auto;
    }

    .approvals-status-select,
    .approvals-vehicle-input,
    .approvals-driver-input {
      min-height: 34px;
      font-size: 12px;
      border-radius: 12px;
      border-color: #d7e2eb;
      box-shadow: none;
    }

    .approvals-status-select {
      grid-column: 1;
      grid-row: 1;
      font-weight: 800;
      border-width: 2px;
      padding-inline: 12px 30px;
      background-position: right 10px center;
      background-size: 12px 8px;
    }

    .approvals-status-select.status-pending-live {
      border-color: #9fc2ff;
      box-shadow: 0 0 0 6px rgba(90, 145, 255, 0.18);
      color: #b97300;
      background-color: #fffdfa;
    }

    .approvals-status-select.status-approved-live {
      border-color: #8fb0ff;
      box-shadow: 0 0 0 6px rgba(102, 145, 255, 0.16);
      color: #215cf4;
      background-color: #fdfefe;
    }

    .approvals-status-select.status-cancelled-live {
      border-color: #efb4ad;
      box-shadow: 0 0 0 6px rgba(199, 59, 52, 0.12);
      color: #c73b34;
      background-color: #fffdfd;
    }

    .approvals-status-select.status-completed-live {
      border-color: #93d8a9;
      box-shadow: 0 0 0 6px rgba(32, 145, 77, 0.12);
      color: #20914d;
      background-color: #fcfffd;
    }

    .approvals-vehicle-input {
      grid-column: 2;
      grid-row: 1;
    }

    .approvals-driver-input {
      grid-column: 1;
      grid-row: 2;
    }

    .approvals-note-input {
      grid-column: 1 / span 2;
      min-height: 34px;
      font-size: 12px;
      border-radius: 12px;
      border: 1px solid #d7e2eb;
      box-shadow: none;
    }

    .approvals-save-btn {
      grid-column: 2;
      grid-row: 2;
      min-height: 34px;
      min-width: 64px;
      justify-self: end;
      padding-inline: 10px;
      font-weight: 700;
      border-radius: 12px;
      font-size: 12px;
    }

    .approvals-archive-btn {
      grid-column: 1;
      grid-row: 3;
      min-height: 34px;
      padding-inline: 12px;
      border-radius: 12px;
      border: 1px solid rgba(32, 80, 56, 0.16);
      background: #eef6f0;
      color: #245038;
      font-size: 12px;
      font-weight: 800;
      transition: background 0.18s ease, transform 0.18s ease;
    }

    .approvals-archive-btn:hover {
      background: #e2efe6;
      transform: translateY(-1px);
    }

    .archive-actions-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      align-items: flex-end;
      width: 100%;
      max-width: 220px;
      margin-left: auto;
    }

    .archive-actions-row {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      width: 100%;
    }

    .approvals-restore-btn {
      min-height: 34px;
      padding-inline: 12px;
      border-radius: 12px;
      border: 1px solid rgba(32, 80, 56, 0.16);
      background: #eef6f0;
      color: #245038;
      font-size: 12px;
      font-weight: 800;
    }

    .approvals-delete-btn {
      min-height: 34px;
      padding-inline: 12px;
      border-radius: 12px;
      border: 1px solid rgba(199, 59, 52, 0.2);
      background: #fff1ef;
      color: #c73b34;
      font-size: 12px;
      font-weight: 800;
    }

    .approvals-view-btn {
      grid-column: 1 / span 2;
      grid-row: 4;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 36px;
      width: 100%;
      border-radius: 999px;
      border: 2px solid #d9e5ef;
      background: #ffffff;
      color: #213453;
      font-size: 12px;
      font-weight: 800;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .approvals-view-btn svg {
      width: 18px;
      height: 18px;
    }

    .approvals-view-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(16, 42, 67, 0.08);
    }

    .approvals-status-box {
      display: inline-flex;
      align-items: center;
      padding: 6px;
      background: #f2f4f7;
      border-radius: 14px;
    }

    .approvals-status-pill {
      min-width: 138px;
      padding-inline: 14px;
      font-weight: 800;
      border-width: 0;
      background-image: none;
    }

    .approvals-status-pill.pending {
      background-color: #fff7dc;
      color: #b97300;
      box-shadow: inset 0 0 0 2px #f3d64c;
    }

    .approvals-status-pill.approved {
      background-color: #edf3ff;
      color: #215cf4;
      box-shadow: inset 0 0 0 2px #cbdcff;
    }

    .approvals-status-pill.completed {
      background-color: #eafff1;
      color: #20914d;
      box-shadow: inset 0 0 0 2px #93d8a9;
    }

    .approvals-status-pill.cancelled {
      background-color: #fff0ef;
      color: #c73b34;
      box-shadow: inset 0 0 0 2px #efb4ad;
    }

    .approval-details-modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: rgba(12, 32, 23, 0.55);
      z-index: 1200;
    }

    .approval-details-modal.open {
      display: flex;
    }

    .approval-details-dialog {
      width: min(920px, 100%);
      max-height: calc(100vh - 48px);
      overflow-y: auto;
      background: #fffdfa;
      border-radius: 28px;
      border: 1px solid rgba(46, 122, 76, 0.16);
      box-shadow: 0 24px 48px rgba(8, 34, 22, 0.18);
      padding: 28px;
    }

    .approval-details-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }

    .approval-details-head h3 {
      margin: 0;
      font-size: 30px;
      color: #172c22;
    }

    .approval-details-head p {
      margin: 8px 0 0;
      color: #6b7a6f;
      font-size: 15px;
    }

    .approval-details-close {
      border: 0;
      background: transparent;
      font-size: 38px;
      line-height: 1;
      color: #617162;
      cursor: pointer;
    }

    .approval-details-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .approval-detail-card {
      padding: 18px 20px;
      border-radius: 18px;
      background: #ffffff;
      border: 1px solid rgba(46, 122, 76, 0.12);
      box-shadow: 0 8px 20px rgba(14, 82, 48, 0.06);
    }

    .approval-detail-card strong {
      display: block;
      margin-bottom: 8px;
      font-size: 13px;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #56705d;
    }

    .approval-detail-card span {
      display: block;
      font-size: 18px;
      font-weight: 700;
      color: #1a2c21;
      line-height: 1.45;
      word-break: break-word;
    }

    .approval-detail-card--wide {
      grid-column: 1 / -1;
    }

    @media (max-width: 1400px) {
      .approvals-action-cell {
        min-width: 320px;
      }

      .approvals-actions-form {
        max-width: 340px;
        grid-template-columns: 1fr;
      }

      .approvals-status-select,
      .approvals-vehicle-input,
      .approvals-driver-input,
      .approvals-save-btn {
        grid-column: auto;
        grid-row: auto;
      }

      .approvals-save-btn {
        width: 100%;
        justify-self: stretch;
      }

      .approvals-view-btn {
        grid-column: auto;
      }
    }

    @media (max-width: 992px) {
      .approvals-toolbar {
        align-items: stretch;
      }

      .approvals-search {
        min-width: 100%;
      }

      .approvals-card-shell {
        padding: 12px !important;
      }

      .approvals-table {
        min-width: 900px;
      }

      .approvals-table th,
      .approvals-table td {
        padding: 12px 10px;
      }

      .approval-details-grid {
        grid-template-columns: 1fr;
      }

      .approval-detail-card--wide {
        grid-column: auto;
      }
    }

    .driver-layout {
      display: grid;
      grid-template-columns: minmax(340px, 460px) minmax(0, 1fr);
      gap: 20px;
      align-items: start;
    }

    .driver-right-column {
      display: grid;
      gap: 20px;
      align-items: start;
    }

    .driver-roster-card,
    .calendar-compact-card,
    .vehicle-info-card {
      background: #ffffff;
      border-radius: 18px;
      padding: 18px;
      box-shadow: 0 8px 20px rgba(7, 94, 63, 0.12);
      border: 1px solid rgba(7, 94, 63, 0.08);
    }

    .driver-roster-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .driver-roster-head h5,
    .calendar-compact-card h5,
    .vehicle-info-card h5 {
      margin: 0;
      font-size: 20px;
    }

    .driver-roster-head p,
    .calendar-compact-card p,
    .vehicle-info-card p {
      margin: 4px 0 0 0;
      color: #6b7a6f;
      font-size: 13px;
    }

    .driver-meta-table {
      width: 100%;
      border-collapse: collapse;
    }

    .driver-meta-table th,
    .driver-meta-table td {
      padding: 14px 10px;
      border-bottom: 1px solid rgba(18, 49, 33, 0.1);
      vertical-align: middle;
    }

    .driver-meta-table th {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #5d6c62;
    }

    .driver-name {
      font-weight: 700;
      color: #173726;
      line-height: 1.2;
    }

    .driver-phone,
    .vehicle-registration {
      color: #6c7a72;
      font-size: 12px;
      margin-top: 4px;
    }

    .driver-status-badge {
      border-radius: 999px;
      padding: 6px 12px;
      font-weight: 700;
      font-size: 12px;
      display: inline-block;
    }

    .driver-status-badge.available {
      background: #e5f7ec;
      color: #1f9d55;
    }

    .driver-status-badge.on-trip {
      background: #ffe6e2;
      color: #d93d2f;
    }

    .driver-status-badge.off-duty {
      background: #fff0d7;
      color: #c57a00;
    }

    .calendar-compact-card .table {
      margin-bottom: 0;
    }

    .calendar-compact-card .table th,
    .calendar-compact-card .table td {
      font-size: 13px;
    }

    .calendar-compact-card .calendar-cell {
      height: 88px !important;
      padding: 8px !important;
    }

    .calendar-compact-card .calendar-entry {
      font-size: 11px;
      padding: 4px 5px;
    }

    .calendar-compact-card .calendar-entry small {
      font-size: 10px;
      line-height: 1.25;
    }

    .calendar-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }

    .calendar-toolbar-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .vehicle-info-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .vehicle-info-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.9fr);
      gap: 16px;
      align-items: start;
    }

    .vehicle-scroll-wrap {
      max-height: 420px;
      overflow: auto;
      border-radius: 14px;
      border: 1px solid rgba(18, 49, 33, 0.1);
      background: #fffdfa;
    }

    .vehicle-mini-table {
      width: 100%;
      border-collapse: collapse;
    }

    .vehicle-mini-table th,
    .vehicle-mini-table td {
      border: 1px solid rgba(18, 49, 33, 0.12);
      padding: 10px 12px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .vehicle-mini-table th {
      background: #f6efe5;
      color: #173726;
      font-size: 12px;
      letter-spacing: 0.8px;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    .vehicle-mini-table td {
      font-size: 13px;
      background: #fffdfa;
      color: #183928;
    }

    .driver-board-wrap {
      overflow: auto;
      border-radius: 16px;
      border: 1px solid rgba(18, 49, 33, 0.14);
      background: #fffdfa;
    }

    .driver-board-table {
      width: 100%;
      min-width: 1080px;
      border-collapse: collapse;
    }

    .driver-board-table th,
    .driver-board-table td {
      border: 1px solid rgba(18, 49, 33, 0.14);
      vertical-align: top;
    }

    .driver-board-tophead th {
      background: #15784d;
      color: #fff;
      text-transform: uppercase;
      text-align: center;
      font-size: 13px;
      letter-spacing: 0.5px;
      padding: 7px 8px;
    }

    .driver-board-subhead th {
      background: #fffdfa;
      color: #1b3527;
      text-transform: uppercase;
      text-align: center;
      font-size: 9px;
      padding: 5px 6px;
      white-space: nowrap;
    }

    .board-driver-cell,
    .board-vehicle-cell {
      width: 195px;
      background: #fff;
      text-align: center;
      padding: 10px 8px;
    }

    .board-driver-name,
    .board-plate,
    .board-vehicle,
    .board-registration {
      font-weight: 800;
      text-transform: uppercase;
      line-height: 1.15;
    }

    .board-driver-name,
    .board-plate {
      font-size: 16px;
    }

    .board-driver-phone,
    .board-vehicle,
    .board-registration {
      margin-top: 4px;
      font-size: 12px;
    }

    .board-driver-actions {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-top: 8px;
    }

    .board-driver-action {
      border: none;
      border-radius: 999px;
      padding: 4px 8px;
      font-weight: 700;
      font-size: 10px;
      background: #eaf0ec;
      color: #173726;
    }

    .board-driver-action.delete {
      background: #ffe6e2;
      color: #c83528;
    }

    .board-slot {
      width: 112px;
      min-width: 112px;
      min-height: 88px;
      padding: 5px 6px;
      cursor: pointer;
      font-weight: 800;
      text-transform: uppercase;
    }

    .board-slot.available {
      background: #ffffff;
      color: #183123;
    }

    .board-slot.approved,
    .board-slot.completed,
    .board-slot.on-trip {
      background: #74ff41;
      color: #132515;
    }

    .board-slot.off-duty {
      background: #f4c542;
      color: #33210a;
    }

    .board-slot.leave {
      background: #f42e1d;
      color: #ffffff;
    }

    .board-slot-time {
      font-size: 11px;
      line-height: 1.1;
      margin-bottom: 3px;
    }

    .board-slot-task {
      font-size: 10px;
      line-height: 1.2;
      word-break: break-word;
    }

    .board-slot.board-slot-linked {
      box-shadow: inset 0 0 0 2px rgba(19, 37, 21, 0.14);
    }

    .board-slot-empty {
      font-size: 10px;
      line-height: 1.2;
      opacity: 0.9;
    }

    @media (max-width: 1200px) {
      .driver-layout {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 900px) {
      .vehicle-info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
<div class="app-shell">
