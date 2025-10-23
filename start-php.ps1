# Parameters
param(
  [string]$ProjectRoot = 'e:\AGrilinkWebClone',
  [string]$Port = '8000',
  [string]$HostName = 'localhost'
)

# Finds php.exe from typical Winget locations and starts a PHP dev server
$php = $null

# 1) Try Winget Links shortcut first
$wingetLink = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Links\php.exe'
if (Test-Path $wingetLink) {
  $php = $wingetLink
}

# 2) Search common install roots if not found
if (-not $php) {
  $roots = @(
    (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'),
    'C:\Program Files',
    'C:\Program Files (x86)'
  )
  foreach ($r in $roots) {
    if (Test-Path $r) {
      try {
        $found = Get-ChildItem -Path $r -Recurse -Filter php.exe -ErrorAction SilentlyContinue |
          Select-Object -First 1 -ExpandProperty FullName
        if ($found) { $php = $found; break }
      } catch {}
    }
  }
}

if (-not $php) {
  Write-Error 'php.exe not found. Please close and reopen the terminal, then run this script again.'
  exit 1
}

 $projectRoot = $ProjectRoot
 $listen = ("{0}:{1}" -f $HostName, $Port)

 Write-Host ("Using PHP at: {0}" -f $php)
 Write-Host ("Starting PHP dev server on http://{0} serving {1}" -f $listen, $projectRoot)

 & $php -S $listen -t $projectRoot
