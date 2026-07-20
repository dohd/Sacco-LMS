#!/bin/sh
# start server on a different terminal
gnome-terminal -- sh -c "php -S localhost:8000 -t public; bash"
