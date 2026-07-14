# Legacy product calculation case studies

These files preserve the former Brutto, Netto, and company-user calculation scenarios as migration references.
They are intentionally outside `tests/` because they depend on the installation-specific product ID `4575`, print
results instead of asserting them, and therefore are not deterministic PHPUnit tests.

The active replacements live under `tests/unit/` and `tests/integration/`. New assertions should be added there; the
legacy files can be removed after every relevant scenario has a deterministic replacement.
