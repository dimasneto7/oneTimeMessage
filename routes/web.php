<?php

use Illuminate\Support\Facades\Route;

// apresentar o formulário de criação da mensagem
Route::get('/', 'Main@index')->name('main_index');
Route::post('/init', 'Main@init')->name('main_init');

// confirmação do envio da mensagem
Route::get('/confirm/{purl}', 'Main@confirm')->name('main_confirm');

// leitura da mensagem
Route::get('/read/{purl}', 'Main@read')->name('main_read');




/*
1. emissor cria uma mensagem
2. é enviado um email de confirmação ao emissor com purl
3. a) emissor clica no link do email e regressa à aplicação para página de confirmação
3. b) receptor recebe email com purl para ler a aplicão
4. receptor clica no link do seu email e é encaminhado para a aplicação onde vê a mensagem
    (sistema envia email ao emissor a confirmar que a mensagem foi lida)
*/
