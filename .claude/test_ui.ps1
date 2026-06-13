# Direct browser automation test
# Load the Chrome extension tools and test the UI

Write-Host "Starting member UI verification test..."
Write-Host "Frontend URL: http://localhost:5173"
Write-Host "Member: member@snt-berezka.ru / Test1238"

# Open browser and navigate
Write-Host "`nOpening browser..."
start http://localhost:5173/login

# Wait for page load
Start-Sleep -Seconds 2

Write-Host "Browser should now show login page"
Write-Host "`nTo verify manually:"
Write-Host "1. Login as member@snt-berezka.ru / Test1238"
Write-Host "2. Check sidebar menu for 'Мои начисления' (not 'Финансы')"
Write-Host "3. Check top-right header for role 'Член СНТ'"
