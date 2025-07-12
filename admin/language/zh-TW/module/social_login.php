<?php
// ===================================================
// 管理後台繁體中文語言檔
// 檔案路徑：admin/language/zh-TW/extension/social_login/module/social_login.php
// ===================================================

// 標題
$_['heading_title'] = '社群登入模組';

// 文字
$_['text_extension'] = '擴充功能';
$_['text_success'] = '成功：您已成功修改社群登入模組設定！';
$_['text_edit'] = '編輯社群登入模組';
$_['text_enabled'] = '啟用';
$_['text_disabled'] = '停用';
$_['text_home'] = '首頁';
$_['text_extensions'] = '擴充功能';

// 分頁標籤
$_['tab_general'] = '一般設定';
$_['tab_facebook'] = 'Facebook 設定';
$_['tab_google'] = 'Google 設定';
$_['tab_line'] = 'LINE 設定';

// 輸入欄位
$_['entry_status'] = '狀態';
$_['entry_facebook_app_id'] = 'Facebook 應用程式 ID';
$_['entry_facebook_app_secret'] = 'Facebook 應用程式密鑰';
$_['entry_google_client_id'] = 'Google 用戶端 ID';
$_['entry_google_client_secret'] = 'Google 用戶端密鑰';
$_['entry_line_channel_id'] = 'LINE 頻道 ID';
$_['entry_line_channel_secret'] = 'LINE 頻道密鑰';

// 按鈕
$_['button_save'] = '儲存';
$_['button_back'] = '返回';
$_['button_cancel'] = '取消';

// 說明文字
$_['help_facebook_app_id'] = '請輸入您從 Facebook 開發者平台取得的應用程式 ID。';
$_['help_facebook_app_secret'] = '請輸入您從 Facebook 開發者平台取得的應用程式密鑰。';
$_['help_google_client_id'] = '請輸入您從 Google Cloud Console 取得的用戶端 ID。';
$_['help_google_client_secret'] = '請輸入您從 Google Cloud Console 取得的用戶端密鑰。';
$_['help_line_channel_id'] = '請輸入您從 LINE 開發者平台取得的頻道 ID。';
$_['help_line_channel_secret'] = '請輸入您從 LINE 開發者平台取得的頻道密鑰。';

// 錯誤訊息
$_['error_permission'] = '警告：您沒有修改社群登入模組的權限！';
$_['error_facebook_app_id'] = 'Facebook 應用程式 ID 為必填欄位！';
$_['error_facebook_app_secret'] = 'Facebook 應用程式密鑰為必填欄位！';
$_['error_google_client_id'] = 'Google 用戶端 ID 為必填欄位！';
$_['error_google_client_secret'] = 'Google 用戶端密鑰為必填欄位！';
$_['error_line_channel_id'] = 'LINE 頻道 ID 為必填欄位！';
$_['error_line_channel_secret'] = 'LINE 頻道密鑰為必填欄位！';

// 統計資訊
$_['text_statistics'] = '統計資訊';
$_['text_total_social_customers'] = '社群登入會員總數';
$_['text_facebook_users'] = 'Facebook 使用者';
$_['text_google_users'] = 'Google 使用者';
$_['text_line_users'] = 'LINE 使用者';

// 社群帳號管理
$_['text_social_accounts'] = '社群帳號管理';
$_['column_customer'] = '會員';
$_['column_provider'] = '社群平台';
$_['column_social_id'] = '社群 ID';
$_['column_social_email'] = '社群信箱';
$_['column_date_added'] = '綁定日期';
$_['column_action'] = '操作';

// 按鈕
$_['button_unlink'] = '解除綁定';
$_['button_view'] = '檢視';
$_['button_filter'] = '篩選';
$_['button_clear'] = '清除';

// 確認訊息
$_['text_confirm_unlink'] = '您確定要解除此社群帳號的綁定嗎？';

// 設定說明
$_['text_setup_instructions'] = '設定說明';
$_['text_facebook_setup'] = 'Facebook 設定步驟：<br/>1. 前往 <a href="https://developers.facebook.com/" target="_blank">Facebook 開發者平台</a><br/>2. 建立新應用程式<br/>3. 新增 Facebook 登入產品<br/>4. 設定 OAuth 重新導向 URI：%s<br/>5. 複製應用程式 ID 和密鑰至此處';
$_['text_google_setup'] = 'Google 設定步驟：<br/>1. 前往 <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a><br/>2. 建立新專案或選擇現有專案<br/>3. 啟用 Google+ API 或 Google Identity APIs<br/>4. 建立 OAuth 2.0 憑證<br/>5. 設定授權重新導向 URI：%s<br/>6. 複製用戶端 ID 和密鑰至此處';
$_['text_line_setup'] = 'LINE 設定步驟：<br/>1. 前往 <a href="https://developers.line.biz/" target="_blank">LINE 開發者平台</a><br/>2. 建立 LINE Login 頻道<br/>3. 設定回呼網址：%s<br/>4. 複製頻道 ID 和密鑰至此處';

// 篩選選項
$_['text_filter_provider'] = '社群平台';
$_['text_filter_customer'] = '會員姓名';
$_['text_filter_email'] = '社群信箱';
$_['text_all_providers'] = '所有平台';

// 狀態文字
$_['text_status_active'] = '啟用';
$_['text_status_inactive'] = '停用';

// 排序選項
$_['text_sort_customer_name'] = '會員姓名';
$_['text_sort_provider'] = '社群平台';
$_['text_sort_date_added'] = '綁定日期';

// 分頁文字
$_['text_pagination'] = '顯示第 %d 到 %d 筆資料（共 %d 筆）';

// 匯出功能
$_['text_export'] = '匯出';
$_['text_export_csv'] = '匯出 CSV';
$_['text_export_excel'] = '匯出 Excel';

// 批次操作
$_['text_batch_actions'] = '批次操作';
$_['text_select_action'] = '選擇操作';
$_['text_delete_selected'] = '刪除選中的';
$_['text_export_selected'] = '匯出選中的';

// 警告訊息
$_['warning_no_selection'] = '請先選擇要操作的項目！';
$_['warning_delete_confirm'] = '您確定要刪除選中的社群登入記錄嗎？此操作無法復原！';

// 成功訊息
$_['success_batch_delete'] = '成功刪除 %d 筆社群登入記錄。';
$_['success_export'] = '資料匯出成功。';

// 維護功能
$_['text_maintenance'] = '維護功能';
$_['text_clean_orphaned'] = '清理孤立記錄';
$_['text_orphaned_records'] = '孤立記錄（客戶已刪除）';
$_['text_clean_confirm'] = '您確定要清理所有孤立的社群登入記錄嗎？';
$_['success_clean_orphaned'] = '成功清理 %d 筆孤立記錄。';

// 儀表板統計
$_['text_dashboard_title'] = '社群登入統計';
$_['text_total_users'] = '總用戶數';
$_['text_monthly_growth'] = '月增長率';
$_['text_most_popular'] = '最受歡迎';
$_['text_conversion_rate'] = '轉換率';

// 圖表標籤
$_['text_chart_monthly'] = '月度新增用戶';
$_['text_chart_platform'] = '平台分佈';
$_['text_chart_trend'] = '使用趨勢';

// 設定檢查
$_['text_config_check'] = '設定檢查';
$_['text_config_status'] = '設定狀態';
$_['text_config_valid'] = '設定正確';
$_['text_config_invalid'] = '設定有誤';
$_['text_config_missing'] = '設定缺失';

// 測試功能
$_['text_test_connection'] = '測試連線';
$_['text_test_facebook'] = '測試 Facebook';
$_['text_test_google'] = '測試 Google';
$_['text_test_line'] = '測試 LINE';
$_['text_test_success'] = '連線測試成功';
$_['text_test_failed'] = '連線測試失敗';

// 日誌功能
$_['text_logs'] = '操作日誌';
$_['text_view_logs'] = '檢視日誌';
$_['text_clear_logs'] = '清除日誌';
$_['text_log_date'] = '日期';
$_['text_log_action'] = '操作';
$_['text_log_details'] = '詳細資料';

// 備份功能
$_['text_backup'] = '資料備份';
$_['text_backup_settings'] = '備份設定';
$_['text_restore_settings'] = '還原設定';
$_['text_backup_success'] = '設定備份成功';
$_['text_restore_success'] = '設定還原成功';
?>