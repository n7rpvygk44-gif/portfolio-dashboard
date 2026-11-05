<?php
// Description des contenus modifiables depuis le backoffice.
// Ajouter un champ ici suffit pour qu'il apparaisse dans le formulaire.

declare(strict_types=1);

final class Admin
{
    // Types de fiches de la base de connaissances (les quatre premiers existaient déjà).
    public const KB_CATEGORIES = ['Procédure', 'Guide', 'Documentation', 'Mémo', 'Notion', 'Glossaire'];

    // Domaines techniques de la base de connaissances (vide = non classé).
    public const KB_DOMAINS = ['', 'Hébergement et déploiement', 'Systèmes', 'Réseau', 'Sécurité', 'Supervision', 'Outils et développement'];

    public const COLLECTIONS = [
        'realisations' => [
            'label' => 'Réalisations',
            'public' => '/realisations/',
            'fields' => [
                'titre' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Adresse (slug)', 'type' => 'text', 'help' => 'Laisser vide pour la générer depuis le titre.'],
                'contexte' => ['label' => 'Contexte', 'type' => 'select', 'options' => ['Formation', 'Stage', 'Personnel']],
                'date' => ['label' => 'Date', 'type' => 'date'],
                'resume' => ['label' => 'Résumé (aussi utilisé pour le référencement)', 'type' => 'textarea'],
                'competences' => ['label' => 'Compétences (codes séparés par des virgules, ex. B1.1, B1.4)', 'type' => 'list'],
                'outils' => ['label' => 'Outils (séparés par des virgules)', 'type' => 'list'],
                'kb' => ['label' => 'Parcours « Reproduire ce projet » : fiches de la base de connaissances, dans l\'ordre (slugs séparés par des virgules)', 'type' => 'list'],
                'contenu' => ['label' => 'Contenu (Markdown)', 'type' => 'markdown'],
            ],
        ],
        'kb' => [
            'label' => 'Base de connaissances',
            'public' => '/base-de-connaissances/',
            'fields' => [
                'titre' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Adresse (slug)', 'type' => 'text', 'help' => 'Laisser vide pour la générer depuis le titre.'],
                'categorie' => ['label' => 'Catégorie (type de fiche)', 'type' => 'select', 'options' => self::KB_CATEGORIES,
                    'help' => 'Procédure : pas à pas · Guide : découvrir un outil · Notion : comprendre un concept · Glossaire : définition courte.'],
                'domaine' => ['label' => 'Domaine', 'type' => 'select', 'options' => self::KB_DOMAINS],
                'outil' => ['label' => 'Outil concerné', 'type' => 'text'],
                'niveau' => ['label' => 'Niveau', 'type' => 'select', 'options' => ['', 'Débutant', 'Intermédiaire', 'Avancé']],
                'duree' => ['label' => 'Durée estimée (ex. 20 min)', 'type' => 'text'],
                'systeme' => ['label' => 'Système concerné (ex. macOS, Linux)', 'type' => 'text'],
                'versions' => ['label' => 'Versions testées (ex. PHP 8.3, Git 2.45)', 'type' => 'text'],
                'verifie_le' => ['label' => 'Dernière vérification', 'type' => 'date', 'help' => 'Date à laquelle la procédure a été refaite et fonctionnait.'],
                'prerequis' => ['label' => 'Fiches à lire avant (slugs séparés par des virgules)', 'type' => 'list'],
                'statut' => ['label' => 'Statut', 'type' => 'select', 'options' => ['Active', 'Archive'], 'help' => 'Archive : méthode ancienne, gardée pour l\'historique (un bandeau le signale).'],
                'resume' => ['label' => 'Résumé (aussi utilisé pour le référencement)', 'type' => 'textarea'],
                'contenu' => ['label' => 'Contenu (Markdown)', 'type' => 'markdown'],
            ],
        ],
        'veille' => [
            'label' => 'Veille technologique',
            'public' => '/veille/',
            'fields' => [
                'titre' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Adresse (slug)', 'type' => 'text', 'help' => 'Laisser vide pour la générer depuis le titre.'],
                'date' => ['label' => 'Date', 'type' => 'date'],
                'source' => ['label' => 'Source', 'type' => 'text'],
                'url' => ['label' => 'Lien de la source', 'type' => 'text'],
                'resume' => ['label' => 'Résumé', 'type' => 'textarea'],
                'contenu' => ['label' => 'Analyse (Markdown)', 'type' => 'markdown'],
            ],
        ],
    ];

    public const PROFILE_FIELDS = [
        'nom' => ['label' => 'Nom complet', 'type' => 'text', 'required' => true],
        'titre' => ['label' => 'Titre', 'type' => 'text'],
        'accroche' => ['label' => 'Accroche (page d\'accueil)', 'type' => 'textarea'],
        'objectif' => ['label' => 'Objectif professionnel', 'type' => 'textarea'],
        'email' => ['label' => 'Adresse e-mail', 'type' => 'text'],
        'linkedin' => ['label' => 'Lien LinkedIn (https://www.linkedin.com/in/…)', 'type' => 'url'],
        'github' => ['label' => 'Lien GitHub', 'type' => 'url'],
        'cv' => ['label' => 'Chemin du CV (ex. /assets/cv.pdf)', 'type' => 'text'],
        'parcours' => ['label' => 'Parcours (Markdown)', 'type' => 'markdown'],
        'sujet_veille' => ['label' => 'Sujet de veille', 'type' => 'textarea'],
    ];

    // Construit un élément à partir des données envoyées par le formulaire.
    public static function fromPost(array $fields, array $post): array
    {
        $item = [];
        foreach ($fields as $name => $field) {
            $value = trim((string) ($post[$name] ?? ''));
            if ($field['type'] === 'list') {
                $value = array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
            } elseif ($field['type'] === 'url' && !preg_match('#^https://[^\s"<>]+$#i', $value)) {
                // Seules les adresses en https:// sont gardées : un lien « javascript: » serait dangereux.
                $value = '';
            } elseif ($field['type'] === 'select' && !in_array($value, $field['options'], true)) {
                $value = $field['options'][0];
            }
            $item[$name] = $value;
        }
        return $item;
    }

    public static function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text));
        return trim($text, '-') ?: 'element';
    }
}
