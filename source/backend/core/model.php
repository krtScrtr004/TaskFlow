<?php

interface Model {
    public static function find($id): ?self;
    public static function all(): mixed;
    public static function create(mixed $data): void;

    public function save(): bool;
    public function delete(): bool;

    // public function fill(array $data): void; 
}