FixtureCommandController
========================

This command is mostly a wrapper for the great package [DavidBadura/Fixtures](https://github.com/DavidBadura/Fixtures).

Fixture Example
---------------

```yaml
user:
  properties:
    class: TYPO3\Party\Domain\Model\Person
  data:
    myUser:
      accounts: ['@account:myAccout']
      name: '@name:myName'

name:
  properties:
    class: TYPO3\Party\Domain\Model\PersonName
  data:
    myName:
      firstName: 'Marc'
      lastName: 'Neuhaus'

account:
  properties:
    class: TYPO3\Flow\Security\Account
  data:
    myAccount:
      accountIdentifier: 'admin'
      authenticationProviderName: 'MyProvider'
      credentialsSource: '<flow::askPassword(Please specify a passwort for the admin user)>'
      party: '@@user:myUser'
      roles: ['@role:admin']

role:
  properties:
    class: TYPO3\Flow\Security\Policy\role
    constructor: ['identifier']
  data:
    admin:
      identifier: 'My.Package:Administrator'
```

Included Services
-----------------

**Simple function to ask for a password during import and put in the hashed value**

```
<flow::askPassword(Please specify a passwort for the admin user)>
```

**Function to hash a specified string to a valid password hash**

```
<flow::hash(myPassword)>
```

Execution
---------

```
./flow fixture:import Data/Fixtures/MyFixture.yaml
```
