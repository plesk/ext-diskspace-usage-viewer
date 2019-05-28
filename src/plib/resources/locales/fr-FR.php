<?php
// Copyright 1999-2019. Plesk International GmbH.
$messages = [
  'buttonHook' => [
    'description' => 'Trouver les fichiers occupant le plus d\'espace disque et libérer de l\'espace',
    'title' => 'Utilisation de l\'espace disque',
  ],
  'home' => [
    'message' => [
      'cleanupFinished' => 'Nettoyage terminé',
      'deleteFailed' => 'Impossible de supprimer : %%path%%',
      'requestFailed' => 'Échec de l\'opération',
    ],
    'tab' => [
      'files' => [
        'button' => [
          'delete' => 'Supprimer sélection',
          'refresh' => 'Actualiser',
        ],
        'col' => [
          'name' => 'Nom',
          'path' => 'Chemin',
          'size' => 'Taille',
        ],
        'title' => 'Plus gros fichiers',
      ],
      'usage' => [
        'button' => [
          'cleanup' => 'Nettoyer',
          'delete' => 'Supprimer sélection',
        ],
        'cleanupDialog' => [
          'backupDays' => 'Sauvegarder fichiers de plus de (jours)',
          'backups' => 'Sauvegardes système',
          'button' => 'Nettoyer',
          'cache' => 'Fichiers cache/temp',
          'description' => 'Cette action efface tous les fichiers cache/temp et les sauvegardes système créés avant le nombre de jours indiqué. Les sauvegardes utilisateur ne sont pas concernées par ce processus. Soyez patient, cela peut être long !',
          'title' => 'Nettoyer l\'espace Web',
        ],
        'col' => [
          'name' => 'Nom',
          'size' => 'Taille',
          'type' => 'Type',
        ],
        'deleteDialog' => [
          'button' => 'Supprimer',
          'description' => 'Voulez-vous vraiment supprimer les éléments sélectionnés ? Cette action est irréversible !',
          'title' => 'Supprimer les éléments sélectionnés ?',
        ],
        'title' => 'Utilisation de l\'espace disque',
        'type' => [
          'dir' => 'Répertoire',
          'file' => 'Fichier',
        ],
      ],
    ],
    'task' => [
      'done' => 'Liste des plus gros fichiers mise à jour',
      'running' => 'Mise à jour de la liste des plus gros fichiers...',
    ],
  ],
  'others' => '[autres]',
  'title' => 'Diskspace Usage Viewer',
];