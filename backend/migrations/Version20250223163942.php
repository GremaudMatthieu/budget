<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250223163942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration adds the views for the budget plan, budget envelope, ledger entry, user entities, event store, encryption keys, and refresh tokens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE budget_envelope_ledger_entry_view (id INT AUTO_INCREMENT NOT NULL, budget_envelope_uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, monetary_amount VARCHAR(13) NOT NULL, entry_type VARCHAR(6) NOT NULL, description VARCHAR(13) DEFAULT \'\' NOT NULL, user_uuid VARCHAR(36) NOT NULL, INDEX idx_budget_envelope_ledger_entry_view_budget_envelope_uuid (budget_envelope_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_envelope_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, current_amount VARCHAR(13) NOT NULL, targeted_amount VARCHAR(13) NOT NULL, name VARCHAR(50) NOT NULL, currency VARCHAR(3) NOT NULL, user_uuid VARCHAR(36) NOT NULL, is_deleted TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_3C39B684D17F50A6 (uuid), INDEX idx_budget_envelope_view_user_uuid (user_uuid), INDEX idx_budget_envelope_view_uuid (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_plan_income_entry_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, income_name VARCHAR(35) NOT NULL, income_amount VARCHAR(13) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6EBA23A7D17F50A6 (uuid), INDEX idx_budget_plan_income_entry_view_uuid (uuid), INDEX idx_budget_plan_income_entry_budget_plan_view_uuid (budget_plan_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_plan_need_entry_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, need_name VARCHAR(35) NOT NULL, need_amount VARCHAR(13) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C103D027D17F50A6 (uuid), INDEX idx_budget_plan_need_entry_view_uuid (uuid), INDEX idx_budget_plan_need_entry_budget_plan_view_uuid (budget_plan_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_plan_saving_entry_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, saving_name VARCHAR(35) NOT NULL, saving_amount VARCHAR(13) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1BFB9A9AD17F50A6 (uuid), INDEX idx_budget_plan_saving_entry_view_uuid (uuid), INDEX idx_budget_plan_saving_entry_budget_plan_view_uuid (budget_plan_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_plan_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, user_uuid VARCHAR(36) NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_3A47CFDCD17F50A6 (uuid), INDEX idx_budget_plan_view_user_uuid (user_uuid), INDEX idx_budget_plan_view_uuid (uuid), INDEX idx_budget_plan_view_date (date), UNIQUE INDEX unique_budget_plan_for_user (user_uuid, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_plan_want_entry_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, want_name VARCHAR(35) NOT NULL, want_amount VARCHAR(13) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_16E8543D17F50A6 (uuid), INDEX idx_budget_plan_want_entry_view_uuid (uuid), INDEX idx_budget_plan_want_entry_budget_plan_view_uuid (budget_plan_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE encryption_keys (id INT AUTO_INCREMENT NOT NULL, user_id VARCHAR(36) NOT NULL, encryption_key LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_store (id INT AUTO_INCREMENT NOT NULL, stream_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, event_name VARCHAR(255) NOT NULL, stream_version INT DEFAULT 0 NOT NULL, request_id VARCHAR(36) NOT NULL, payload JSON NOT NULL, meta_data JSON NOT NULL, occurred_on DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_stream_id (stream_id), INDEX idx_event_name (event_name), INDEX idx_user_id (user_id), INDEX idx_occurred_on (occurred_on), UNIQUE INDEX unique_stream_version (stream_id, stream_version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, email VARCHAR(320) NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(50) NOT NULL, lastname VARCHAR(50) NOT NULL, language_preference VARCHAR(35) NOT NULL, consent_given TINYINT(1) NOT NULL, consent_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, roles JSON NOT NULL, password_reset_token VARCHAR(64) DEFAULT NULL, password_reset_token_expiry DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_847CE747D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE budget_envelope_ledger_entry_view');
        $this->addSql('DROP TABLE budget_envelope_view');
        $this->addSql('DROP TABLE budget_plan_income_entry_view');
        $this->addSql('DROP TABLE budget_plan_need_entry_view');
        $this->addSql('DROP TABLE budget_plan_saving_entry_view');
        $this->addSql('DROP TABLE budget_plan_view');
        $this->addSql('DROP TABLE budget_plan_want_entry_view');
        $this->addSql('DROP TABLE encryption_keys');
        $this->addSql('DROP TABLE event_store');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE user_view');
    }
}
