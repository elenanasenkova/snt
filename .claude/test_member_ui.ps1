# Test member user interface requirements
param(
    [string]$BaseUrl = "http://localhost:5173",
    [string]$ApiUrl = "http://localhost/api"
)

$ErrorActionPreference = "SilentlyContinue"

# 1. Login as member
Write-Host "1. Logging in as member..."
$loginBody = @{
    email = "member@snt-berezka.ru"
    password = "Test1238"
} | ConvertTo-Json -Encoding UTF8

$loginResp = Invoke-WebRequest -Uri "$ApiUrl/auth.php" `
    -Method POST `
    -ContentType "application/json; charset=UTF-8" `
    -Body ([System.Text.Encoding]::UTF8.GetBytes($loginBody)) `
    -ErrorAction Stop

$auth = $loginResp.Content | ConvertFrom-Json
$token = $auth.token
Write-Host "Token: $($token.Substring(0,20))..."

# 2. Get current user info
Write-Host "`n2. Getting current user info..."
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json; charset=UTF-8"
}

$userResp = Invoke-WebRequest -Uri "$ApiUrl/user.php" `
    -Headers $headers `
    -ErrorAction Stop

$user = $userResp.Content | ConvertFrom-Json
Write-Host "User: $($user.fullName), Role: $($user.roleName), RoleId: $($user.roleId)"

# Build results
$checks = @(
    @{
        item = "Role name from API"
        status = if ($user.roleName -eq "member") { "PASS" } else { "FAIL" }
        detail = "Role is '$($user.roleName)' (expected 'member')"
    },
    @{
        item = "Role ID from API"
        status = if ($user.roleId -eq 5) { "PASS" } else { "FAIL" }
        detail = "RoleId is $($user.roleId) (expected 5 for member)"
    }
)

$issues = @()
if ($user.roleName -ne "member") { $issues += "API returns wrong role: $($user.roleName)" }
if ($user.roleId -ne 5) { $issues += "API returns wrong roleId: $($user.roleId)" }

@{
    ok = ($issues.Count -eq 0)
    role = "member"
    checks = $checks
    issues = $issues
    user = @{ name = $user.fullName; roleName = $user.roleName; roleId = $user.roleId }
} | ConvertTo-Json -Depth 3
