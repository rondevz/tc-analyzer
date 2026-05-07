Refactor Candidates
After TDD cycle, look for:

Duplication → Extract function/class
Long methods → Break into private helpers (keep tests on public interface)
Shallow modules → Combine or deepen
Feature envy → Move logic to where data lives
Primitive obsession → Introduce value objects
Existing code the new code reveals as problematic

During the refactor phase, after the tests pass, run `vendor/bin/rector` process to optimize the syntax, and `vendor/bin/phpstan` analyse to ensure no strict typing errors were introduced.
