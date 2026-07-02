param(
    [int]$Port = 8080,
    [string]$RuleName = "Libre Mercado Web 8080"
)

$existingRule = Get-NetFirewallRule -DisplayName $RuleName -ErrorAction SilentlyContinue

if ($null -eq $existingRule) {
    New-NetFirewallRule `
        -DisplayName $RuleName `
        -Direction Inbound `
        -Action Allow `
        -Protocol TCP `
        -LocalPort $Port `
        -Profile Private

    Write-Host "Regla creada: $RuleName (TCP $Port, perfil Private)."
} else {
    Set-NetFirewallRule -DisplayName $RuleName -Enabled True -Profile Private
    Write-Host "La regla ya existia y fue habilitada: $RuleName."
}

Write-Host ""
Write-Host "Comparte esta URL con otros equipos de tu misma red local:"
Write-Host "  http://IP_DEL_HOST:$Port/index.php"
Write-Host ""
Write-Host "Para ver tu IP local puedes ejecutar:"
Write-Host "  ipconfig"
