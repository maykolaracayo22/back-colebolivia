#requires -Version 5.1
<#
 Script: teacher_gradebook_flow.ps1
 Flujo completo para un docente: Login -> Crear Acta (si no existe) -> Crear Evaluación -> Guardar Notas/Auto/PE -> Recompute -> Lock -> Publish -> Mostrar Acta
 Ajusta las variables iniciales según tu entorno.
#>

# CONFIGURACION
$BaseUrl        = $env:API_BASE_URL        ? $env:API_BASE_URL        : 'http://localhost:8000'
$TeacherEmail   = $env:TEACHER_EMAIL       ? $env:TEACHER_EMAIL       : 'teacher1@example.com'
$TeacherPass    = $env:TEACHER_PASSWORD    ? $env:TEACHER_PASSWORD    : 'password'
$Year           = 2025
$Nivel          = 'primaria'
$Grado          = 2
$Seccion        = 'A'
$Term           = 'T1'
$MateriaId      = 1  # Cambia al ID real de subject
$MateriaNombre  = 'Matemáticas'
$DocenteId      = 1  # Cambia al ID real del teacher
$DocenteNombre  = 'Prof X'
$StudentId      = 1  # Cambia al ID real del student
$NoteValor      = 8
$AutoValor      = 1
$PeValor        = 5

Write-Host '== Login =='
$loginBody = @{ email = $TeacherEmail; password = $TeacherPass; device = 'script-teacher' } | ConvertTo-Json
$loginResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/login" -ContentType 'application/json' -Body $loginBody
$Token = $loginResp.token
if (-not $Token) { throw 'No se obtuvo token. Verifica credenciales.' }
Write-Host "Token OK (rol: $($loginResp.user.role))"
$Headers = @{ Authorization = "Bearer $Token" }

# Helper: buscar acta existente
Write-Host '== Buscar Actas existentes =='
$actasUrl = "$BaseUrl/api/actas?year=$Year&term=$Term"
$actasList = Invoke-RestMethod -Method GET -Uri $actasUrl -Headers $Headers
$ActaId = $null
if ($actasList.data) {
  foreach ($a in $actasList.data) {
    if ($a.nivel -eq $Nivel -and $a.grado -eq $Grado -and $a.seccion -eq $Seccion -and $a.materia_id -eq $MateriaId) {
      $ActaId = $a.id
      break
    }
  }
}

if (-not $ActaId) {
  Write-Host '== Crear Acta =='
  $createActaBody = @{ year=$Year; nivel=$Nivel; grado=$Grado; seccion=$Seccion; term=$Term; materia_id=$MateriaId; materia_nombre=$MateriaNombre; docente_id=$DocenteId; docente_nombre=$DocenteNombre } | ConvertTo-Json
  $actaResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/actas" -Headers $Headers -ContentType 'application/json' -Body $createActaBody
  $ActaId = $actaResp.id
  if (-not $ActaId) { throw 'No se pudo crear Acta.' }
  Write-Host "Acta creada ID: $ActaId"
} else {
  Write-Host "Acta existente ID: $ActaId"
}

# Crear evaluación (simple). No hay GET general de evaluaciones, asumimos crear.
Write-Host '== Crear Evaluación =='
$evalBody = @{ acta_id = $ActaId; nombre = 'P1'; peso = 0 } | ConvertTo-Json
$evalResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/acta-evaluations" -Headers $Headers -ContentType 'application/json' -Body $evalBody
$EvaluationId = $evalResp.id
if (-not $EvaluationId) { throw 'No se pudo crear evaluación.' }
Write-Host "Evaluación creada ID: $EvaluationId"

# Guardar notas + auto + pe
Write-Host '== Guardar Notas/Auto/PE =='
$itemsBody = @{
  notes = @(@{ student_id = $StudentId; evaluation_id = $EvaluationId; valor = $NoteValor });
  auto  = @(@{ student_id = $StudentId; valor = $AutoValor });
  pe    = @(@{ student_id = $StudentId; valor = $PeValor });
} | ConvertTo-Json -Depth 5
$saveResp = Invoke-RestMethod -Method PUT -Uri "$BaseUrl/api/actas/$ActaId/items" -Headers $Headers -ContentType 'application/json' -Body $itemsBody
Write-Host "SaveItems respuesta: $($saveResp.message)"

# Recompute
Write-Host '== Recompute Acta =='
$recomputeResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/actas/$ActaId/recompute" -Headers $Headers
Write-Host "Recompute: $($recomputeResp.message)"

# Lock acta
Write-Host '== Lock Acta =='
$lockResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/actas/$ActaId/lock" -Headers $Headers
Write-Host "Estado tras lock: $($lockResp.status)"

# Publish (puede fallar si faltan permisos notas.consolidar)
Write-Host '== Publish Acta (intento) =='
try {
  $publishResp = Invoke-RestMethod -Method POST -Uri "$BaseUrl/api/actas/$ActaId/publish" -Headers $Headers
  Write-Host "Estado tras publish: $($publishResp.status)"
} catch {
  Write-Warning "Publish falló: $($_.Exception.Message) (verifica permiso notas.consolidar y estado LOCKED)"
}

# Mostrar acta final
Write-Host '== Mostrar Acta =='
$showResp = Invoke-RestMethod -Method GET -Uri "$BaseUrl/api/actas/$ActaId" -Headers $Headers
Write-Host "Acta ID: $($showResp.id) Status: $($showResp.status)"
Write-Host 'Flujo completado.'
