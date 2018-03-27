In order to implement migration you would need to do few things:
1. Set links between classes and activities
2. Set links between sessions and locations

For these you have two hooks hook_openy_daxko2_programs_csv_row_alter() and
openy_daxko2_example_openy_daxko2_categories_csv_row_alter()

See example of the module where we pull random activities and locations.
