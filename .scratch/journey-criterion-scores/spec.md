# Journey criterion scores

Taiga story: [#115 — Expor pontuação agregada por critério no resultado da jornada](https://tree.taiga.io/project/jotaf-dashboard/us/115) (internal ID `9642784`).

The completed journey detail exposes a persisted `score_breakdown` list for professional experience, time spent on the role, English proficiency, education, professional certifications, and competencies. Each entry has a stable category key, Portuguese and English names, and an unweighted performance score from 0 to 100. Weighted contributions and criterion weights remain internal. The overall total continues to use the existing weighted calculation. Persist the breakdown atomically when finalizing a journey so later changes to scoring data do not alter historic results. Ongoing journeys and completed journeys finalized before this field existed return `null`.

This response contract aligns with the completed result-screen story #105 and resolves the conflict between the original weighted-contribution wording in Taiga #115 and the frontend's requirement to hide weights and contributions.
