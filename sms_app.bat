@echo off

rem -------------------------------------------------------------
rem  App command line bootstrap script for Windows.
rem -------------------------------------------------------------

@setlocal

set APP_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%APP_PATH%sms_app" %*

@endlocal