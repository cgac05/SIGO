#!/usr/bin/env powershell
<#
.DESCRIPTION
    Script para desplegar cambios a AWS Elastic Beanstalk
.NOTES
    Requiere: Git, Node.js, EB CLI configurada
#>

$ErrorActionPreference = "Stop"

Write-Host "🚀 INICIANDO DEPLOYMENT A PRODUCCIÓN" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# 1. Verificar cambios sin commitear
Write-Host "`n1️⃣  Verificando estado del repositorio..." -ForegroundColor Yellow
$status = git status --porcelain
if ($status) {
    Write-Host "⚠️  Hay cambios sin commitear:" -ForegroundColor Red
    Write-Host $status
    Write-Host "`nPor favor, commit o stash los cambios antes de desplegar" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Repositorio limpio" -ForegroundColor Green

# 2. Compilar assets
Write-Host "`n2️⃣  Compilando assets..." -ForegroundColor Yellow
npm ci --omit=dev
if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️  npm ci falló, intentando npm install..." -ForegroundColor Yellow
    npm install --omit=dev
}
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error compilando assets" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Assets compilados exitosamente" -ForegroundColor Green

# 3. Verificar manifest.json
Write-Host "`n3️⃣  Verificando manifest.json..." -ForegroundColor Yellow
if (-not (Test-Path "public/build/manifest.json")) {
    Write-Host "❌ manifest.json no encontrado" -ForegroundColor Red
    exit 1
}
Write-Host "✅ manifest.json existe" -ForegroundColor Green

# 4. Hacer commit de los cambios de build
Write-Host "`n4️⃣  Commitando cambios de build..." -ForegroundColor Yellow
git add public/build
git add vite.config.js
git add .env
git add .ebextensions/
git commit -m "Build: Compilar assets para producción" || Write-Host "ℹ️  Sin cambios nuevos para commitear" -ForegroundColor Cyan

# 5. Desplegar a Elastic Beanstalk
Write-Host "`n5️⃣  Desplegando a AWS Elastic Beanstalk..." -ForegroundColor Yellow
eb deploy

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✅ DEPLOYMENT EXITOSO" -ForegroundColor Green
    Write-Host "=====================================" -ForegroundColor Green
    Write-Host "La aplicación está siendo actualizada en:" -ForegroundColor Cyan
    Write-Host "http://injuve.us-east-1.elasticbeanstalk.com" -ForegroundColor Cyan
    Write-Host "`nEspera 2-3 minutos para que se complete el deployment" -ForegroundColor Yellow
} else {
    Write-Host "`n❌ ERROR EN EL DEPLOYMENT" -ForegroundColor Red
    Write-Host "Revisa los logs con: eb logs" -ForegroundColor Yellow
    exit 1
}
