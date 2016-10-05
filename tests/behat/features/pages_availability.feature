Feature: Check pages avilability
  @fast-tests @headless
  Scenario Outline: Check all pages are OK
    When I go to "<page>"
    Then I should get a 200 HTTP response
    Examples:
      | page |
      | / |
      | membership |
      | locations |
      | locations/andover_ymca_community_center |
      | camps |
      | camps/camp_menogyn |
      | all_y_schedules |
      | health__fitness |
      | health__fitness/personal_training |
      | health__fitness/personal_training/personal-trainer-schedules |
      | swimming |
      | child_care__preschool |
      | kid__teen_activities |
      | our_cause |
      | about |
      | contact_us |
      | give |
      | news |
      | jobs |
      | suppliers |
      | blog |
      | privacy_policy |
