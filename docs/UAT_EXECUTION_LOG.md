# Arewa Civic Tracker — User Acceptance Testing (UAT) Execution Log

This log records results provided by the RAWYOD/InnoBytes tester during interactive acceptance testing. A **PASS** is a tester-reported result, not a claim that the assistant independently executed browser or device steps. Each test is recorded once and subsequent retests must be added as distinct entries rather than silently overwriting historical findings.

## Web application

| Test ID | Test case | Result | Reported date (Africa/Lagos) | Evidence / observations | Next action |
| --- | --- | --- | --- | --- | --- |
| ACT-WEB-001 | Ward selection and form layout — select Damaturu; search/select Damaturu Central; confirm single compact field and aligned form | **PASS** | 2026-11-02 | Tester reported PASS in ChatGPT UAT session; no screenshot attached to result. | Proceed to ACT-WEB-002 |

## Next planned test (not executed)

**ACT-WEB-002 — Manual ward entry and separation from Community**

On **New Field Report**, choose a permitted LGA (e.g., Damaturu), type an unlisted ward name in the Ward selector, use the **Ward not listed? Add …** option, verify that the new Ward appears selected and **Community** remains independently editable. Do not submit a permanent test report in this test; saving and reviewing full submissions is covered separately. Expected: one ward value, no duplicate fields, no layout distortion. Record PASS/FAIL only after the tester confirms the result.

## Result definitions

- **PASS** — Tester confirmed every expected outcome.
- **FAIL** — Tester observed at least one unmet expectation; record exact defect.
- **PARTIAL** — Some expectations met; others unverified or incorrect.
- **BLOCKED** — Test could not be executed due to environment, access, or prerequisite.
- **NOT RUN** — No acceptance result reported.

Avoid using real citizen records or deleting production records during UAT.
