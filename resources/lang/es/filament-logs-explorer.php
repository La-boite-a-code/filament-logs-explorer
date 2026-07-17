<?php

declare(strict_types=1);

return [

    'navigation' => [
        'label' => 'Registros',
        'group' => 'Sistema',
    ],

    'page' => [
        'title' => 'Registros',
        'heading' => 'Explorador de registros',
        'subheading' => 'Explora, consulta y busca en los archivos de registro de tu aplicación, agrupados por canal.',
    ],

    'actions' => [
        'refresh' => 'Actualizar',
        'refresh_tooltip' => 'Volver a analizar los archivos de registro en el disco',
    ],

    'channels' => [
        'untracked' => 'Otros archivos',
        'file_count' => '{0} Ningún archivo|{1} :count archivo|[2,*] :count archivos',
    ],

    'list' => [
        'empty' => [
            'heading' => 'No se encontraron archivos de registro',
            'description' => 'No se encontró ningún archivo de registro legible para los canales configurados.',
        ],
        'size' => 'Tamaño',
        'modified' => 'Modificado :time',
        'view' => 'Ver',
        'unreadable' => 'Ilegible',
    ],

    'viewer' => [
        'title' => 'Archivo de registro',
        'channel' => 'Canal',
        'size' => 'Tamaño',
        'modified' => 'Modificado',
        'position' => 'Archivo :current de :total',
        'search_placeholder' => 'Buscar en este archivo…',
        'previous_file' => 'Archivo anterior',
        'next_file' => 'Archivo siguiente',
        'previous_match' => 'Coincidencia anterior',
        'next_match' => 'Coincidencia siguiente',
        'go_to_top' => 'Ir al principio',
        'go_to_bottom' => 'Ir al final',
        'matches' => ':current / :total',
        'no_matches' => 'Sin coincidencias',
        'lines' => '{0} vacío|{1} :count línea|[2,*] :count líneas',
        'truncated_tail' => 'Archivo grande: mostrando los últimos :size (:lines). Descarga el archivo para verlo completo.',
        'truncated_head' => 'Archivo grande: mostrando los primeros :size (:lines). Descarga el archivo para verlo completo.',
        'empty' => 'Este archivo está vacío.',
        'unreadable' => 'No se pudo leer este archivo.',
        'copy' => 'Copiar',
        'copied' => '¡Copiado!',
        'download' => 'Descargar',
        'delete' => 'Eliminar',
        'close' => 'Cerrar',
        'keyboard_hint' => 'Atajos: / buscar · n / N coincidencia siguiente / anterior · g / G principio / final',
    ],

    'delete' => [
        'modal_heading' => '¿Eliminar este archivo de registro?',
        'modal_description' => '¿Eliminar permanentemente «:name»? Esta acción no se puede deshacer.',
        'success_title' => 'Archivo eliminado',
        'success_body' => '«:name» se ha eliminado.',
        'failed_title' => 'Error al eliminar',
        'failed_body' => 'No se pudo eliminar «:name».',
    ],

];
