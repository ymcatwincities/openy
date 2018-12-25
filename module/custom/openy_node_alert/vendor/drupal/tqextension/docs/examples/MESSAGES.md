# Find messages on the page

Every message could be on English language. In this case they will be processed by `t()` function and translated. If some complex strings required then placeholders could be used.

```gherkin
Then should see the error message "!name field is required."
  | !name | E-mail address  |
```

or this can be easily replaced by message, for example, on German language:

```gherkin
Then Then should see the error message "Das Feld „E-Mail-Adresse” ist erforderlich."
```

Multiple messages also could be handled:

```gherkin
Then should see the following error messages:
  | !name field is required.  | !name => E-mail address |
  | !name field is required.  | !name => Site name      |
```

## Other examples

```gherkin
And should not see the success message "The configuration options have been saved."
# Uncomment the next line and comment previous to use only German locale:
# And should not see the success message "Die Konfigurationsoptionen wurden gespeichert."
```
