<?php

use Illuminate\Support\Facades\Route;

// W produkcji serwujemy tylko API.
// Frontend (Vue) jest budowany do index.html i serwowany przez serwer WWW (Nginx/Apache)
// lub w trybie deweloperskim przez Vite.
