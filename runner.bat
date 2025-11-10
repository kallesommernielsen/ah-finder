@echo off
:start
git pull -p
call refresh
timeout /t 300 /nobreak

goto start
