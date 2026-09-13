<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Entretien;
use App\Models\Mission;
use App\Models\MissionAuditGroup;
use App\Models\MissionTeamMember;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Models\Role;
use App\Models\Service;
use App\Models\SwotCategory;
use App\Models\SwotEntry;
use App\Models\SwotTemplate;
use App\Models\User;
use App\Services\Questionnaires\QuestionnaireRuntimeService;
use App\Services\Raci\RaciAssignmentService;
use App\Services\Risk\RiskRegistryPromotionService;
use App\Services\Swot\SwotAnalyticsService;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Jeu de validation fonctionnelle visible, isolé par le préfixe TEST-E2E.
 * À exécuter explicitement : php artisan db:seed --class=EndToEndPlatformValidationSeeder --force
 */
class EndToEndPlatformValidationSeeder extends Seeder
{
    public const REFERENCE = 'TEST-E2E-20260913-M01';

    public function run(): void
    {
        if (Mission::query()->where('reference', self::REFERENCE)->exists()) {
            $this->command?->warn('Scénario déjà présent : '.self::REFERENCE);
            return;
        }

        DB::transaction(function (): void {
            $is = Department::query()->where('code', 'IS')->firstOrFail();
            $dgcpt = Department::query()->where('code', 'DGCPT')->firstOrFail();

            $auditPole = Department::query()->create([
                'name' => 'TEST-E2E — Pôle Audit Test', 'code' => 'TEST-PAT', 'type' => 'pole',
                'parent_department_id' => $is->id, 'active' => true,
                'description' => 'Structure temporaire de validation fonctionnelle complète.',
            ]);
            $newYork = Department::query()->create([
                'name' => 'TEST-E2E — Trésorerie de New York', 'code' => 'TEST-TNY', 'type' => 'administration',
                'parent_department_id' => $dgcpt->id, 'active' => true,
            ]);
            $itUnit = Department::query()->create([
                'name' => 'TEST-E2E — Service informatique New York', 'code' => 'TEST-TNY-IT', 'type' => 'service',
                'parent_department_id' => $newYork->id, 'active' => true,
            ]);
            $accountingUnit = Department::query()->create([
                'name' => 'TEST-E2E — Service comptabilité New York', 'code' => 'TEST-TNY-CPT', 'type' => 'service',
                'parent_department_id' => $newYork->id, 'active' => true,
            ]);

            $chief = $this->user('Chef', 'Audit Test', 'chef.audit.test@example.test', $auditPole, 'inspecteur_adjoint', 'Responsable du Pôle Audit Test');
            $iv1 = $this->user('Aline', 'Vérification', 'aline.verification@example.test', $auditPole, 'inspecteur_verificateur', 'Inspectrice vérificatrice');
            $iv2 = $this->user('Bruno', 'Contrôle', 'bruno.controle@example.test', $auditPole, 'inspecteur_verificateur', 'Inspecteur vérificateur');
            $iva = $this->user('Carine', 'Audit', 'carine.audit@example.test', $auditPole, 'inspecteur_verificateur_adjoint', 'Inspectrice vérificatrice adjointe');
            $director = $this->user('Directeur', 'New York', 'directeur.newyork@example.test', $newYork, 'directeur', 'Directeur de la Trésorerie');
            $itChief = $this->user('Chef', 'Informatique NY', 'chef.informatique.ny@example.test', $itUnit, 'chef_service', 'Chef du service informatique');
            $accountingChief = $this->user('Chef', 'Comptabilité NY', 'chef.comptabilite.ny@example.test', $accountingUnit, 'chef_service', 'Chef du service comptabilité');

            $auditPole->update(['supervisor_user_id' => $chief->id]);
            $newYork->update(['supervisor_user_id' => $director->id]);
            $itUnit->update(['supervisor_user_id' => $itChief->id]);
            $accountingUnit->update(['supervisor_user_id' => $accountingChief->id]);

            $mission = Mission::query()->create([
                'organisation' => 'TEST-E2E — Audit de la Trésorerie de New York',
                'reference' => self::REFERENCE,
                'objet' => 'Évaluer la gouvernance, la sécurité SI et la fiabilité comptable.',
                'description' => 'Scénario de validation fonctionnelle de bout en bout.',
                'date_debut' => now()->startOfDay(), 'date_fin' => now()->addDays(14)->startOfDay(),
                'auditeur_id' => $chief->id, 'department_id' => $auditPole->id,
                'mission_type' => 'audit_si', 'mission_status' => Mission::STATUS_EN_COURS,
                'priority' => 'high', 'sensitivity_level' => 'internal', 'confidentiality_level' => 'internal',
            ]);

            $team = collect([
                [$chief, MissionTeamMember::ROLE_CHEF_MISSION, true],
                [$iv1, MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR, false],
                [$iv2, MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR, false],
                [$iva, MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT, false],
            ])->map(fn (array $row) => MissionTeamMember::query()->create([
                'mission_id' => $mission->id, 'user_id' => $row[0]->id, 'mission_role' => $row[1],
                'is_lead' => $row[2], 'assigned_at' => now(), 'assigned_by' => $chief->id,
            ]));

            $serviceIt = Service::query()->create([
                'mission_id' => $mission->id, 'code' => 'TEST-SVC-IT', 'nom' => 'Service informatique — Trésorerie de New York',
                'responsable' => $itChief->name, 'chef_service_user_id' => $itChief->id, 'active' => true,
                'audit_priority' => 'high', 'risk_level' => 'high', 'audit_status' => Service::AUDIT_STATUS_IN_AUDIT,
            ]);
            $serviceAccounting = Service::query()->create([
                'mission_id' => $mission->id, 'code' => 'TEST-SVC-CPT', 'nom' => 'Service comptabilité — Trésorerie de New York',
                'responsable' => $accountingChief->name, 'chef_service_user_id' => $accountingChief->id, 'active' => true,
                'audit_priority' => 'medium', 'risk_level' => 'medium', 'audit_status' => Service::AUDIT_STATUS_IN_AUDIT,
            ]);

            $template = QuestionnaireTemplate::query()->create([
                'name' => 'TEST-E2E — Gouvernance, SI et comptabilité', 'slug' => 'test-e2e-gouvernance-si-comptabilite',
                'description' => 'Questionnaire multi-thèmes de validation.', 'mission_type' => 'audit_si',
                'mission_id' => $mission->id, 'department_scope' => [$auditPole->id], 'visibility_scope' => 'department',
                'active' => true, 'version' => 1, 'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
                'review_status' => QuestionnaireTemplate::REVIEW_ADOPTED, 'published_at' => now(), 'adopted_at' => now(),
                'adopted_by' => $chief->id, 'created_by' => $chief->id, 'updated_by' => $chief->id,
            ]);
            $questions = $this->questionnaire($template);

            $groupA = MissionAuditGroup::query()->create([
                'mission_id' => $mission->id, 'name' => 'Équipe A — Gouvernance et sécurité SI',
                'questionnaire_template_id' => $template->id, 'service_id' => $serviceIt->id,
                'interviewed_person' => $itChief->name, 'interviewed_role' => $itChief->fonction,
                'objective' => 'Tester la gouvernance, les accès et la sauvegarde.', 'status' => 'active', 'created_by' => $chief->id,
            ]);
            $groupA->members()->attach([$team[0]->id, $team[1]->id, $team[3]->id]);
            $groupB = MissionAuditGroup::query()->create([
                'mission_id' => $mission->id, 'name' => 'Équipe B — Fiabilité comptable',
                'questionnaire_template_id' => $template->id, 'service_id' => $serviceAccounting->id,
                'interviewed_person' => $accountingChief->name, 'interviewed_role' => $accountingChief->fonction,
                'objective' => 'Tester les rapprochements et la traçabilité.', 'status' => 'active', 'created_by' => $chief->id,
            ]);
            $groupB->members()->attach([$team[0]->id, $team[2]->id]);

            $entretien = Entretien::query()->create([
                'mission_id' => $mission->id, 'service_id' => $serviceIt->id,
                'questionnaire_template_id' => $template->id, 'conducted_by' => $iv1->id,
                'interviewed_person' => $itChief->name, 'interviewed_role' => $itChief->fonction,
                'conducted_at' => now(), 'status' => Entretien::STATUS_DRAFT,
            ]);
            $request = Request::create('/test-e2e/questionnaire', 'POST');
            $request->setUserResolver(fn () => $iv1);
            $runtime = app(QuestionnaireRuntimeService::class);
            $runtime->recordResponses($entretien, $this->responses($questions), $iv1, $request);

            $risk = $entretien->identifiedRisks()->firstOrFail();
            $promotion = app(RiskRegistryPromotionService::class);
            $risk = $promotion->submitForReview($risk, $chief, 'Revue E2E effectuée.');
            $risk = $promotion->approve($risk, $chief, 'Risque validé lors du test E2E.');
            $promotion->promote($risk, $chief, 'Inscription au registre institutionnel E2E.');

            $swot = $this->swot($auditPole, $chief);
            app(SwotAnalyticsService::class)->runMissionAnalysis($swot, $mission, ['actor_id' => $chief->id, 'notes' => 'Analyse SWOT E2E']);
            $raci = $this->raci($auditPole, $chief);
            $roles = $raci->roles()->get();
            app(RaciAssignmentService::class)->assignForMission($raci, $mission, [
                'name' => 'TEST-E2E — RACI mission New York', 'process_label' => 'Réalisation de la mission', 'status' => 'assigned',
                'assignments' => $roles->map(fn (RaciRole $role, int $i) => [
                    'raci_role_id' => $role->id, 'assigned_user_id' => [$chief->id, $iv1->id, $director->id, $itChief->id][$i],
                    'process_label' => 'Réalisation de la mission', 'role_type' => $role->role_type->value,
                    'responsibility_level' => $role->responsibility_level->value, 'status' => 'assigned',
                ])->all(),
            ], $chief);

            $checks = [
                'équipe' => MissionTeamMember::query()->where('mission_id', $mission->id)->count(),
                'groupes' => $mission->auditGroups()->count(),
                'réponses' => $entretien->questionnaireResponses()->count(),
                'questions' => count($questions),
                'SWOT' => $mission->swotAnalyses()->count(),
                'RACI' => $mission->raciMatrices()->count(),
                'risques' => \App\Models\Risque::query()->where(function ($query) use ($mission): void {
                    $query->where('mission_id', $mission->id)
                        ->orWhereIn('source_identified_risk_id', \App\Models\IdentifiedRisk::query()->where('mission_id', $mission->id)->select('id'));
                })->count(),
            ];
            if ($checks['équipe'] !== 4 || $checks['groupes'] !== 2 || $checks['réponses'] !== $checks['questions']
                || $checks['SWOT'] !== 1 || $checks['RACI'] !== 1 || $checks['risques'] < 1) {
                throw new RuntimeException('Le scénario E2E est incomplet : '.json_encode($checks, JSON_UNESCAPED_UNICODE));
            }

            $this->command?->info('Scénario E2E créé : mission #'.$mission->id.' / '.self::REFERENCE);
        });
    }

    private function user(string $prenom, string $name, string $email, Department $department, string $roleSlug, string $fonction): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        return User::query()->create([
            'prenom' => $prenom, 'name' => 'TEST-E2E — '.$name, 'email' => $email,
            'password' => Hash::make(bin2hex(random_bytes(24))), 'department_id' => $department->id,
            'role_id' => $role->id, 'role' => $roleSlug, 'fonction' => $fonction, 'position' => $fonction,
            'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'approved_at' => now(), 'must_change_password' => true,
        ]);
    }

    /** @return array<int, QuestionnaireQuestion> */
    private function questionnaire(QuestionnaireTemplate $template): array
    {
        $definitions = [
            ['Gouvernance et alignement stratégique', 'Pilotage du SI', 'Stratégie et portefeuille', [
                ['GOV-01', 'Le schéma directeur SI est-il formalisé, approuvé et suivi ?', 'Schéma directeur SI; comptes rendus de comité'],
                ['GOV-02', 'Les projets SI sont-ils priorisés selon des critères documentés ?', 'Portefeuille projets; grille de priorisation'],
            ]],
            ['Sécurité et continuité', 'Protection des ressources', 'Accès et sauvegardes', [
                ['SEC-01', 'Les droits d’accès font-ils l’objet de revues périodiques ?', 'Matrice des habilitations; journaux de revue'],
                ['SEC-02', 'Les sauvegardes sont-elles testées par des restaurations documentées ?', 'Rapports de sauvegarde; procès-verbal de restauration'],
            ]],
            ['Fiabilité comptable', 'Contrôles comptables', 'Rapprochement et traçabilité', [
                ['CPT-01', 'Les rapprochements sont-ils réalisés et validés dans les délais ?', 'États de rapprochement signés'],
                ['CPT-02', 'Les anomalies comptables sont-elles tracées jusqu’à leur résolution ?', 'Registre des anomalies; preuves de correction'],
            ]],
        ];
        $questions = [];
        foreach ($definitions as $themeOrder => [$themeTitle, $thematicTitle, $subthemeTitle, $items]) {
            $theme = QuestionnaireSection::query()->create(['questionnaire_template_id' => $template->id, 'title' => $themeTitle, 'section_type' => 'theme', 'sort_order' => $themeOrder + 1]);
            $thematic = QuestionnaireSection::query()->create(['questionnaire_template_id' => $template->id, 'title' => $thematicTitle, 'section_type' => 'thematic', 'parent_section_id' => $theme->id, 'sort_order' => 1]);
            $subtheme = QuestionnaireSection::query()->create(['questionnaire_template_id' => $template->id, 'title' => $subthemeTitle, 'section_type' => 'subtheme', 'parent_section_id' => $thematic->id, 'sort_order' => 1]);
            foreach ($items as $index => [$code, $label, $documents]) {
                $questions[] = QuestionnaireQuestion::query()->create([
                    'questionnaire_section_id' => $subtheme->id, 'code' => $code, 'question' => $label,
                    'question_type' => 'boolean_na', 'required' => true, 'allows_observation' => true,
                    'allows_risk_detection' => true, 'expected_documents' => $documents,
                    'risk_category' => str_starts_with($code, 'SEC') ? 'Cybersécurité' : 'Gouvernance',
                    'risk_level' => $code === 'SEC-02' ? 'critical' : 'medium', 'sort_order' => $index + 1, 'active' => true,
                ]);
            }
        }
        return $questions;
    }

    private function responses(array $questions): array
    {
        return collect($questions)->map(fn (QuestionnaireQuestion $q, int $index) => array_filter([
            'questionnaire_question_id' => $q->id,
            'answer_text' => $index === 3 ? 'Non' : 'Oui',
            'observation' => $index === 3 ? 'Aucun test de restauration formalisé sur les douze derniers mois.' : 'Contrôle présenté et vérifié.',
            'identified_risk' => $index === 3 ? [
                'title' => 'Absence de test périodique de restauration',
                'description' => 'Les sauvegardes existent mais leur restaurabilité n’est pas démontrée.',
                'category' => 'Continuité SI', 'probability' => 4, 'impact' => 5, 'criticality' => 'critical',
                'recommendation' => 'Planifier et documenter un test trimestriel de restauration.',
            ] : null,
        ], fn ($value) => $value !== null))->all();
    }

    private function swot(Department $department, User $actor): SwotTemplate
    {
        $template = SwotTemplate::query()->create([
            'department_id' => $department->id, 'name' => 'TEST-E2E — SWOT Trésorerie New York',
            'slug' => 'test-e2e-swot-tresorerie-new-york', 'code' => 'TEST-SWOT-TNY', 'analysis_scope' => 'mission',
            'active' => true, 'version' => 1, 'lifecycle_status' => SwotTemplate::STATUS_PUBLISHED, 'created_by' => $actor->id,
        ]);
        foreach ([['Forces','STR','strength','Compétences SI disponibles'],['Faiblesses','WEA','weakness','Tests de restauration non formalisés'],['Opportunités','OPP','opportunity','Automatisation du suivi des contrôles'],['Menaces','THR','threat','Indisponibilité prolongée des données']] as $i => [$name,$code,$type,$title]) {
            $category = SwotCategory::query()->create(['swot_template_id'=>$template->id,'name'=>$name,'code'=>$code,'category_type'=>$type,'weight'=>1,'sort_order'=>$i]);
            SwotEntry::query()->create(['swot_template_id'=>$template->id,'swot_category_id'=>$category->id,'department_id'=>$department->id,'title'=>$title,'impact_level'=>'high','priority_level'=>'high','weight'=>1.5,'is_active'=>true,'sort_order'=>$i]);
        }
        return $template;
    }

    private function raci(Department $department, User $actor): RaciTemplate
    {
        $template = RaciTemplate::query()->create([
            'department_id'=>$department->id,'name'=>'TEST-E2E — RACI mission New York','slug'=>'test-e2e-raci-mission-new-york',
            'code'=>'TEST-RACI-TNY','analysis_scope'=>'mission','active'=>true,'version'=>1,
            'lifecycle_status'=>RaciTemplate::STATUS_PUBLISHED,'created_by'=>$actor->id,
        ]);
        foreach ([['Réalisateur','R','responsible','high'],['Autorité','A','accountable','high'],['Consulté','C','consulted','moderate'],['Informé','I','informed','low']] as $i => [$name,$code,$type,$level]) {
            RaciRole::query()->create(['raci_template_id'=>$template->id,'department_id'=>$department->id,'name'=>$name,'code'=>$code,'role_type'=>$type,'responsibility_level'=>$level,'sort_order'=>$i]);
        }
        return $template;
    }
}
