# Running Tests
These instructions explain how you can run tests.


## Behat
### Requirements
- Ansible 1.9.4+ http://docs.ansible.com/ansible/intro_installation.html
- Docker https://docs.docker.com/engine/installation/

### Run full test suite
1. Execute command

    ```
    $ cd profiles/contrib/openy
    $ sh runtests.sh
    ```
2. Open http://site.com/profiles/contrib/openy/build/reports/behat in browser.

### Run selenium container + Behat tests in usual way
In order to run only selenium container + behat in usual way:

```
$ cd profiles/contrib/openy
$ sh runtests.sh --tags run_selenium
$ bin/behat
```
    
### Stop selenium container
In order to stop  selenium container:

```
$ cd profiles/contrib/openy
$ sh runtests.sh --tags stop_selenium
```

If necessary, edit behat.local.yml to match your environment.
