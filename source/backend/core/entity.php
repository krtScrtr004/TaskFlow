<?php

interface Entity extends JsonSerializable {
    public function jsonSerialize(): array;
}