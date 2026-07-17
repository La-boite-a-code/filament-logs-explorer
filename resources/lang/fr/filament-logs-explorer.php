<?php

declare(strict_types=1);

return [

    'navigation' => [
        'label' => 'Journaux',
        'group' => 'Système',
    ],

    'page' => [
        'title' => 'Journaux',
        'heading' => 'Explorateur de journaux',
        'subheading' => 'Parcourez, consultez et recherchez dans les fichiers de journaux de votre application, regroupés par canal.',
    ],

    'actions' => [
        'refresh' => 'Actualiser',
        'refresh_tooltip' => 'Relire les fichiers de journaux sur le disque',
    ],

    'channels' => [
        'untracked' => 'Autres fichiers',
        'file_count' => '{0} Aucun fichier|{1} :count fichier|[2,*] :count fichiers',
    ],

    'list' => [
        'empty' => [
            'heading' => 'Aucun fichier de journal trouvé',
            'description' => 'Aucun fichier de journal lisible n’a été trouvé pour les canaux configurés.',
        ],
        'size' => 'Taille',
        'modified' => 'Modifié :time',
        'view' => 'Consulter',
        'unreadable' => 'Illisible',
    ],

    'viewer' => [
        'title' => 'Fichier de journal',
        'channel' => 'Canal',
        'size' => 'Taille',
        'modified' => 'Modifié',
        'position' => 'Fichier :current sur :total',
        'search_placeholder' => 'Rechercher dans ce fichier…',
        'previous_file' => 'Fichier précédent',
        'next_file' => 'Fichier suivant',
        'previous_match' => 'Occurrence précédente',
        'next_match' => 'Occurrence suivante',
        'go_to_top' => 'Aller au début',
        'go_to_bottom' => 'Aller à la fin',
        'matches' => ':current / :total',
        'no_matches' => 'Aucune occurrence',
        'lines' => '{0} vide|{1} :count ligne|[2,*] :count lignes',
        'truncated_tail' => 'Fichier volumineux : affichage des derniers :size (:lines). Téléchargez le fichier pour tout consulter.',
        'truncated_head' => 'Fichier volumineux : affichage des premiers :size (:lines). Téléchargez le fichier pour tout consulter.',
        'empty' => 'Ce fichier est vide.',
        'unreadable' => 'Ce fichier n’a pas pu être lu.',
        'copy' => 'Copier',
        'copied' => 'Copié !',
        'download' => 'Télécharger',
        'close' => 'Fermer',
        'keyboard_hint' => 'Raccourcis : / rechercher · n / N occurrence suivante / précédente · g / G début / fin',
    ],

];
