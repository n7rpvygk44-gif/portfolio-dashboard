<?php
// Lecture et écriture des données au format JSON.
// Chaque "collection" est un fichier data/<nom>.json.

declare(strict_types=1);

final class JsonStore
{
    public function __construct(private string $dir)
    {
    }

    private function path(string $name): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            throw new InvalidArgumentException('Nom de collection invalide');
        }
        return $this->dir . '/' . $name . '.json';
    }

    public function read(string $name, mixed $default = []): mixed
    {
        $file = $this->path($name);
        if (!is_file($file)) {
            return $default;
        }
        $data = json_decode((string) file_get_contents($file), true);
        return $data ?? $default;
    }

    // Écriture atomique : on écrit dans un fichier temporaire puis on le renomme,
    // pour ne jamais laisser un JSON à moitié écrit.
    public function write(string $name, mixed $data): void
    {
        $file = $this->path($name);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        file_put_contents($tmp, $json . "\n", LOCK_EX);
        rename($tmp, $file);
    }

    // Lit, modifie et réécrit un fichier en le verrouillant pendant l'opération :
    // deux visiteurs arrivant en même temps ne peuvent pas effacer le comptage l'un de l'autre.
    public function update(string $name, callable $change, mixed $default = []): mixed
    {
        $lock = fopen($this->path($name) . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $data = $change($this->read($name, $default));
            $this->write($name, $data);
            return $data;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    // Retrouve un élément d'une collection par son identifiant (slug).
    public function find(string $name, string $slug): ?array
    {
        foreach ($this->read($name) as $item) {
            if (($item['slug'] ?? '') === $slug) {
                return $item;
            }
        }
        return null;
    }
}
