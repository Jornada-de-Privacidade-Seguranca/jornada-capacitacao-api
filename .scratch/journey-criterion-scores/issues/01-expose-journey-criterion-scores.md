# Expose journey criterion scores

Taiga: [#115](https://tree.taiga.io/project/jotaf-dashboard/us/115) (internal ID `9642784`)
Status: ready-for-human

## Scope

Expose the weighted score contributions for professional experience, English proficiency, education, professional certifications, and competencies in completed journey detail. Preserve decimal precision and ensure the criterion contributions sum to the persisted total. Update OpenAPI and feature coverage.

## Comments

- Implemented the completed journey detail `criterion_scores` breakdown and documented it in OpenAPI. Feature coverage verifies all five weighted contributions and their exact sum matches the persisted score (`17.631`), while ongoing journeys return a null breakdown.
- Validation: `JourneyFinalizationTest.php` and `UserJourneyDetailTest.php` passed (30 tests, 173 assertions) before the added all-criteria case; the new all-criteria case also passed. `composer quality` passed. `composer check` passed before the final assertion was added.
- OpenAPI lint parsed the document but reports 4 existing `security-defined` errors on unrelated operations and 17 existing warnings; the new schema/example produced no additional lint findings.
- Manual external HTTP check was not run: no local API service/database or test credentials were available, and the only running container belongs to another project. The isolated feature tests exercised `GET /api/journeys/{journey}` through Laravel's HTTP test client.
