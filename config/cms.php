<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS — assets e branding
    |--------------------------------------------------------------------------
    */

    'header_logo' => env('CMS_HEADER_LOGO', 'img/logo_cms.svg'),

    /*
    | Grupo para novos utilizadores (registo em /cms/register). Se vazio, usa o grupo
    | criado na migration `*_create_self_registered_cms_group` (nome: Cadastro público).
    */
    'self_registered_group_id' => env('CMS_SELF_REGISTERED_GROUP_ID'),

];
