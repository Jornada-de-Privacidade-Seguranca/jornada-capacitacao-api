# Expose journey criterion scores

Taiga: [#115](https://tree.taiga.io/project/jotaf-dashboard/us/115) (internal ID `9642784`)
Status: ready-for-human

## Scope

Expose a persisted `score_breakdown` in completed journey detail with normalized 0–100 performance scores for professional experience, time spent on the role, English proficiency, education, professional certifications, and competencies. Include stable category keys and PT/EN names. Keep weighted contributions and criterion weights internal; preserve the existing weighted total calculation. Persist the breakdown atomically at finalization. Ongoing and legacy completed journeys without a stored breakdown return `null`. Update OpenAPI and feature coverage.

## Comments

- Implemented the completed journey detail `criterion_scores` breakdown and documented it in OpenAPI. Feature coverage verifies all five weighted contributions and their exact sum matches the persisted score (`17.631`), while ongoing journeys return a null breakdown.
- Validation: `JourneyFinalizationTest.php` and `UserJourneyDetailTest.php` passed (30 tests, 173 assertions) before the added all-criteria case; the new all-criteria case also passed. `composer quality` passed. `composer check` passed before the final assertion was added.
- OpenAPI lint parsed the document but reports 4 existing `security-defined` errors on unrelated operations and 17 existing warnings; the new schema/example produced no additional lint findings.
- Manual external HTTP check was not run: no local API service/database or test credentials were available, and the only running container belongs to another project. The isolated feature tests exercised `GET /api/journeys/{journey}` through Laravel's HTTP test client.
- PR #47 review correction: public `criterion_scores` exposed weighted contributions and could reveal internal weights; the result screen story #105 instead requires normalized 0–100 scores. Replaced the response with `score_breakdown`, persisted in the same finalization update as the total. Legacy completed journeys return `null`; tests verify stored values remain unchanged after scoring weights change.
