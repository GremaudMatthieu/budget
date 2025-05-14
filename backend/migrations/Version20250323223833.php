<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250323223833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration adds the views for the budget plan, budget envelope, ledger entry, user entities, event store, encryption keys, and refresh tokens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE budget_envelope_ledger_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_envelope_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_plan_income_entry_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_plan_need_entry_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_plan_saving_entry_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_plan_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE budget_plan_want_entry_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE encryption_keys_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE event_store_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE refresh_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_view_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE budget_envelope_ledger_entry_view (id INT NOT NULL, budget_envelope_uuid VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, monetary_amount VARCHAR(13) NOT NULL, entry_type VARCHAR(6) NOT NULL, description VARCHAR(13) DEFAULT \'\' NOT NULL, user_uuid VARCHAR(36) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_budget_envelope_ledger_entry_view_budget_envelope_uuid ON budget_envelope_ledger_entry_view (budget_envelope_uuid)');
        $this->addSql('CREATE TABLE budget_envelope_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_amount VARCHAR(13) NOT NULL, targeted_amount VARCHAR(13) NOT NULL, name VARCHAR(25) NOT NULL, currency VARCHAR(3) NOT NULL, user_uuid VARCHAR(36) NOT NULL, is_deleted BOOLEAN DEFAULT false NOT NULL, context_uuid VARCHAR(36) NOT NULL, context VARCHAR(36) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3C39B684D17F50A6 ON budget_envelope_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_envelope_view_user_uuid ON budget_envelope_view (user_uuid)');
        $this->addSql('CREATE INDEX idx_budget_envelope_view_uuid ON budget_envelope_view (uuid)');
        $this->addSql('CREATE TABLE budget_plan_income_entry_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, income_name VARCHAR(35) NOT NULL, income_amount VARCHAR(13) NOT NULL, category VARCHAR(35) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EBA23A7D17F50A6 ON budget_plan_income_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_income_entry_view_uuid ON budget_plan_income_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_income_entry_budget_plan_view_uuid ON budget_plan_income_entry_view (budget_plan_uuid)');
        $this->addSql('COMMENT ON COLUMN budget_plan_income_entry_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE budget_plan_need_entry_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, need_name VARCHAR(35) NOT NULL, need_amount VARCHAR(13) NOT NULL, category VARCHAR(35) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C103D027D17F50A6 ON budget_plan_need_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_need_entry_view_uuid ON budget_plan_need_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_need_entry_budget_plan_view_uuid ON budget_plan_need_entry_view (budget_plan_uuid)');
        $this->addSql('COMMENT ON COLUMN budget_plan_need_entry_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE budget_plan_saving_entry_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, saving_name VARCHAR(35) NOT NULL, saving_amount VARCHAR(13) NOT NULL, category VARCHAR(35) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1BFB9A9AD17F50A6 ON budget_plan_saving_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_saving_entry_view_uuid ON budget_plan_saving_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_saving_entry_budget_plan_view_uuid ON budget_plan_saving_entry_view (budget_plan_uuid)');
        $this->addSql('COMMENT ON COLUMN budget_plan_saving_entry_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE budget_plan_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, user_uuid VARCHAR(36) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, currency VARCHAR(3) NOT NULL, is_deleted BOOLEAN DEFAULT false NOT NULL, context_uuid VARCHAR(36) NOT NULL, context VARCHAR(36) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A47CFDCD17F50A6 ON budget_plan_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_view_user_uuid ON budget_plan_view (user_uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_view_uuid ON budget_plan_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_view_date ON budget_plan_view (date)');
        $this->addSql('COMMENT ON COLUMN budget_plan_view.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN budget_plan_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE budget_plan_want_entry_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, budget_plan_uuid VARCHAR(36) NOT NULL, want_name VARCHAR(35) NOT NULL, want_amount VARCHAR(13) NOT NULL, category VARCHAR(35) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16E8543D17F50A6 ON budget_plan_want_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_want_entry_view_uuid ON budget_plan_want_entry_view (uuid)');
        $this->addSql('CREATE INDEX idx_budget_plan_want_entry_budget_plan_view_uuid ON budget_plan_want_entry_view (budget_plan_uuid)');
        $this->addSql('COMMENT ON COLUMN budget_plan_want_entry_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE encryption_keys (id INT NOT NULL, user_id VARCHAR(36) NOT NULL, encryption_key TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_encryption_keys_user_id ON encryption_keys (user_id)');
        $this->addSql('COMMENT ON COLUMN encryption_keys.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE event_store (id INT NOT NULL, stream_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, event_name VARCHAR(255) NOT NULL, stream_version INT DEFAULT 0 NOT NULL, stream_name VARCHAR(255) NOT NULL, request_id VARCHAR(36) NOT NULL, payload JSON NOT NULL, meta_data JSON NOT NULL, occurred_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_stream_id ON event_store (stream_id)');
        $this->addSql('CREATE INDEX idx_stream_name ON event_store (stream_name)');
        $this->addSql('CREATE INDEX idx_event_name ON event_store (event_name)');
        $this->addSql('CREATE INDEX idx_event_user_id ON event_store (user_id)');
        $this->addSql('CREATE INDEX idx_occurred_on ON event_store (occurred_on)');
        $this->addSql('CREATE UNIQUE INDEX unique_stream_version ON event_store (stream_id, stream_version)');
        $this->addSql('COMMENT ON COLUMN event_store.occurred_on IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE refresh_tokens (id INT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)');
        $this->addSql('CREATE TABLE user_view (id INT NOT NULL, uuid VARCHAR(36) NOT NULL, email VARCHAR(320) NOT NULL, firstname VARCHAR(50) NOT NULL, lastname VARCHAR(50) NOT NULL, language_preference VARCHAR(35) NOT NULL, consent_given BOOLEAN NOT NULL, consent_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, roles JSON NOT NULL, registration_context VARCHAR(32) NOT NULL, provider_user_id VARCHAR(255) NOT NULL, context_uuid VARCHAR(36) NOT NULL, context VARCHAR(36) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_847CE747D17F50A6 ON user_view (uuid)');
        $this->addSql('COMMENT ON COLUMN user_view.consent_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_view.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE budget_envelope_ledger_entry_view ALTER COLUMN id SET DEFAULT nextval(\'budget_envelope_ledger_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_envelope_view ALTER COLUMN id SET DEFAULT nextval(\'budget_envelope_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_plan_income_entry_view ALTER COLUMN id SET DEFAULT nextval(\'budget_plan_income_entry_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_plan_need_entry_view ALTER COLUMN id SET DEFAULT nextval(\'budget_plan_need_entry_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_plan_saving_entry_view ALTER COLUMN id SET DEFAULT nextval(\'budget_plan_saving_entry_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_plan_view ALTER COLUMN id SET DEFAULT nextval(\'budget_plan_view_id_seq\')');
        $this->addSql('ALTER TABLE budget_plan_want_entry_view ALTER COLUMN id SET DEFAULT nextval(\'budget_plan_want_entry_view_id_seq\')');
        $this->addSql('ALTER TABLE encryption_keys ALTER COLUMN id SET DEFAULT nextval(\'encryption_keys_id_seq\')');
        $this->addSql('ALTER TABLE event_store ALTER COLUMN id SET DEFAULT nextval(\'event_store_id_seq\')');
        $this->addSql('ALTER TABLE refresh_tokens ALTER COLUMN id SET DEFAULT nextval(\'refresh_tokens_id_seq\')');
        $this->addSql('ALTER TABLE user_view ALTER COLUMN id SET DEFAULT nextval(\'user_view_id_seq\')');
        $this->addSql('CREATE TABLE user_oauth (
            id SERIAL PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            provider VARCHAR(255) NOT NULL,
            provider_user_id VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            UNIQUE (provider, provider_user_id),
            UNIQUE (user_id, provider)
        )');
        $this->addSql('COMMENT ON COLUMN user_oauth.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE budget_envelope_ledger_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_envelope_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_plan_income_entry_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_plan_need_entry_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_plan_saving_entry_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_plan_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE budget_plan_want_entry_view_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE encryption_keys_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_store_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE refresh_tokens_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_view_id_seq CASCADE');
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
        $this->addSql('DROP TABLE user_oauth');
    }
}
