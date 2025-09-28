<?php

interface Model {
    public static function find($id): mixed;
    public static function all(): array;
    public static function create(array $data): mixed;

    public function save(): bool;
    public function delete(): bool;

    public function fill(array $data): void; 
}