Feature: Check that environment loader scans it properly
  Scenario: Try execute steps from couple extensions which are used environment loader
    # Try execute step from DummyExtension.
    Given dummy step
    # Try execute step from ExampleExtension.
    And example step
