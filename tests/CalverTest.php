<?php

use Layerok\Restapi\Classes\Calver;

class CalverTest extends TestCase {
    public function testCalver() {
        $userWebClientVersion = Calver::fromString('2024.2.10');

        $this->assertTrue(
            $userWebClientVersion->isOlderThan(
                Calver::fromString('2024.2.11')
            )
        );

        $this->assertTrue(
            $userWebClientVersion->isTheSameAs(
                Calver::fromString('2024.2.10')
            )
        );

        $this->assertFalse(
            $userWebClientVersion->isTheSameAs(
                Calver::fromString('2024.4.10')
            )
        );

        $this->assertFalse(
            $userWebClientVersion->isTheSameAs(
                Calver::fromString('2025.2.10')
            )
        );

        $this->assertFalse(
            $userWebClientVersion->isOlderThan(
                Calver::fromString('2024.1.11')
            )
        );
    }
}
