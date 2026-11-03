# Arewa Civic Tracker — Notification Matrix

**Delivery channels:** in-app Notifications page and top-bar Alerts bell, plus Zoho CPaaS email via a durable database outbox and one-minute scheduled retries. Email links open only authorized case routes. The public visitor role receives no notifications and never receives private attachments.

| Trigger | Recipient | Delivery | Action |
| --- | --- | --- | --- |
| New field report submitted (web or Android) | Active RAWYOD Administrators and MEAL officers | In-app + email | Open review queue |
| Reviewer assigned or deadline changed | Assigned MEAL reviewer | In-app + email | Open report and review |
| Review deadline due/overdue | Assigned MEAL reviewer | In-app + email, once per day | Complete review |
| Clarification requested | Report's focal-person author | In-app + email | Submit clarification |
| Clarification response received | Assigned reviewer | In-app + email | Resume review |
| Verification state changes | Report's focal-person author, excluding actor | In-app + email | Open report to see status |
| MEAL verification complete | Active RAWYOD Administrators, excluding actor | In-app + email | Final approval decision |
| Report progress changes | Report's focal-person author, excluding actor | In-app + email | Read update |
| Report closed or reopened | Report's focal-person author, excluding actor | In-app + email | Inspect outcome |
| Report published or unpublished | Report's focal-person author, excluding actor | In-app + email | See public-visibility status |
| Follow-up note added | Reporter or assigned reviewer (whichever was not actor) | In-app + email | Read follow-up |

**Access and security:** The notification contains the case reference, event name and generic action summary, not case descriptions, GPS, personal data or evidence. Both email and in-app links require authenticated, authorized access to the case. Email delivery retries up to six attempts. Provider outages must not roll back a successful case action. Email provider API keys and verified From address are managed through private environment settings, never GitHub.

**Not currently included:** Android push notifications, public subscriber mailings, staff password invitations, evidence-upload email alerts for unassigned reports, and real-time web socket notifications. These are distinct workflows and should not be assumed active solely because email case alerts are enabled.
