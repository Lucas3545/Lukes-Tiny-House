@echo off
::este script es un lanzador de aplicaciones para Windows
title Menu de aplicaciones
:menu
cls
echo ==============================
echo   Lanzador de aplicaciones Windows
echo ==============================
echo 1. Bloc de notas
echo 2. Calculadora
echo 3. Paint
echo 4. Explorador de archivos
echo 5. Configuración
echo 6. Salir
echo ==============================
set /p opcion=Elige una opcion (1-6): 

if "%opcion%"=="1" start notepad.exe
if "%opcion%"=="2" start calc.exe
if "%opcion%"=="3" start mspaint.exe
if "%opcion%"=="4" start explorer.exe
if "%opcion%"=="5" start ms-settings:
if "%opcion%"=="6" exit
goto menu