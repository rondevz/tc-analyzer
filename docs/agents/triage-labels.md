# Triage Labels

The five canonical triage roles and the strings used to represent them in this repo.

| Role | String |
|---|---|
| Maintainer needs to evaluate | `needs-triage` |
| Waiting on reporter | `needs-info` |
| Fully specified, AFK-agent-ready | `ready-for-agent` |
| Needs human implementation | `ready-for-human` |
| Will not be actioned | `wontfix` |

For local markdown issues, record the current state as a `Status:` line near the top of the issue file, e.g.:

```
Status: needs-triage
```

Update this line in-place as the issue moves through the state machine.
