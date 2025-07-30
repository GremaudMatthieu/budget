<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250730210651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Comprehensive auto-increment fix: reset sequences, fix schema validation, and ensure consecutive IDs';
    }

    public function up(Schema $schema): void
    {
        $sequences = [
            'user_view_id_seq' => 'user_view',
            'budget_envelope_view_id_seq' => 'budget_envelope_view',
            'budget_plan_view_id_seq' => 'budget_plan_view',
            'budget_envelope_ledger_view_id_seq' => 'budget_envelope_ledger_entry_view',
            'budget_plan_income_entry_view_id_seq' => 'budget_plan_income_entry_view',
            'budget_plan_need_entry_view_id_seq' => 'budget_plan_need_entry_view',
            'budget_plan_saving_entry_view_id_seq' => 'budget_plan_saving_entry_view',
            'budget_plan_want_entry_view_id_seq' => 'budget_plan_want_entry_view',
            'encryption_keys_id_seq' => 'encryption_keys',
            'event_store_id_seq' => 'event_store',
            'refresh_tokens_id_seq' => 'refresh_tokens',
            'aggregate_snapshots_id_seq' => 'aggregate_snapshots',
            'user_oauth_id_seq' => 'user_oauth'
        ];

        foreach ($sequences as $sequence => $table) {
            $this->addSql("SELECT setval('$sequence', COALESCE((SELECT MAX(id) FROM $table), 0) + 1, false)");
        }

        $this->addSql('ALTER INDEX user_oauth_provider_provider_user_id_key RENAME TO unique_provider_user');
        $this->addSql('ALTER INDEX user_oauth_user_id_provider_key RENAME TO unique_user_provider');
        $this->addSql('DROP SEQUENCE IF EXISTS budget_envelope_ledger_view_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE budget_envelope_ledger_entry_view_id_seq');
        $this->addSql('SELECT setval(\'budget_envelope_ledger_entry_view_id_seq\', COALESCE((SELECT MAX(id) FROM budget_envelope_ledger_entry_view), 0) + 1, false)');
        $this->addSql('ALTER TABLE budget_envelope_ledger_entry_view ALTER id SET DEFAULT nextval(\'budget_envelope_ledger_entry_view_id_seq\')');
        $this->addSql('ALTER TABLE refresh_tokens ALTER id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
    }
}

