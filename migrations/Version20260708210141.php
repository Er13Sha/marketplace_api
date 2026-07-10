<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708210141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create auth users table for email/password authentication.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS auth_users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, phone_number VARCHAR(32) DEFAULT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_auth_users_email ON auth_users (email)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_auth_users_phone_number ON auth_users (phone_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS auth_users');
    }
}
