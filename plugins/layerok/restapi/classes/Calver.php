<?php

namespace Layerok\Restapi\Classes;

class Calver {
    public function __construct(public int $year, public int $month,  public int $release) {

    }
    public static function fromString(string $version): self {
        $parts = explode('.', $version);

        $year = intval($parts[0]);
        $month = intval($parts[1]);
        $release = intval($parts[2]);

        return new self($year, $month, $release);
    }

    public function isOlderThan(Calver $version): bool {
        if($this->year > $version->year) {
            return false;
        }

        if($this->year < $version->year) {
            return true;
        }

        if($this->month > $version->month) {
            return false;
        }

        if($this->month < $version->month) {
            return true;
        }

        if($this->release > $version->release) {
            return false;
        }

        if($this->release < $version->release) {
            return true;
        }

        // versions are the same
        return false;
    }

    public function isTheSameAs(Calver $version): bool {
        if($this->year != $version->year) {
            return false;
        }

        if($this->month != $version->month) {
            return false;
        }

        if($this->release != $version->release) {
            return false;
        }

        return true;
    }
}
