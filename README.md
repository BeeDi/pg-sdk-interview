# ÉNONCÉ

Un fichier SDK PHP a été confié à plusieurs stagiaires, qui ont chacun leur tour ajouté et modifié des lignes de code. Ce fichier permet, normalement, de faciliter la connexion avec nos API RestFull.
Il intègre plusieurs fonctionnalités :

    Connexion oAuth sur le service Paygreen
    Connexion RestFull
    Paiement comptant / Abonnement / En plusieurs fois / À la livraison
    Remboursement

## PREMIÈRE ÉTAPE : L'ANALYSE

Tu trouveras ci-joint un fichier PHP (https://drive.google.com/file/d/1-cfEPwZmJ09QGpxaOy6AqAUWSbmHt0T0/view) : à toi de l'analyser ! Fais un rapport complet de son code source et de ce qui est mal fait selon toi (en considérant tant le respect des normes de codage et tes bonnes pratiques habituelles). Et d'en conclure : pourrions-nous fournir ce fichier à des agences web qui souhaiteraient nous intégrer ?

### Retours sur la classe PaygreenApiClient

Je vous soumets le document présent pour vous détailler mon processus d'analyse et vous fournir l'avancée de mes modifications.
J'utilise SublimeText.


#### Syntaxe 

- le nommage des méthodes n'est pas valide par rapport aux PSR1, et n'a même pas de cohérence interne (exemple avec ces trois syntaxes différentes pour des méthodes privés `oauth_access`, `setHost`, `IdsAreEmpty`). 
- Il y a de la documentation pour certaines fonctions, écrite en anglais. Par contre, il y a parfois des chiffres qui ne semblent avoir aucun sens (l.47, l.67) et de la documentation qui semble obsolète (l.205-211). 
- il n'y a pas de namespace déclaré ce qui peut poser des problèmes en cas de nommage identique de la classe.
- il y a du code commenté (l.381-382), il faudrait soit le supprimer, soit créer une méthode pour l'ajouter de manière optionnel (par exemple, à utiliser uniquement dans un environnement de production).

- l'exécution de `phpcs` sur le fichier révèle 469 erreurs et 11 warnings sur 235 lignes. 

Recommendation : Un processus d'intégration continue pour valider la syntaxe et la documentation des fichiers créés règlerait facilement ces problèmes. 

Après l'exécution de `phpcbf -w --standard=PSR2 PaygreenApiClient.php` il reste surtout des problèmes de nommages de métodes non standards et l'absence d'un namespace.


#### Architecture

- une seule classe pour implémenter toutes ces fonctionnalitées pose un vrai problème de séparation des responsabilités et rendra la maintenance compliqué (Single Responsibility Principle, Open Closed Principle). Une modification dans la fonctionnalité, ou une tentative d'extension va forcément se pencher dans les entrailles de ces 557 lignes de codes. Cela ne pose pas de problème d'utilisation par un tiers, mais ça augmente considérablement la dette technique.
- tentative d'utilisation d'un pattern singleton pour n'avoir qu'une instance, sauf que le constructeur est public. Cela permet toujours de créer de nouvelles instances du Client. Une très bonne raison de ne pas livrer ce code à des tiers.
- pas de tests, cela pose problème pour les développeurs d'après, comment s'assurer qu'on aura rien cassé après un refactoring ?

On distingue plusieurs responsabilités dans cette classe :
- construire le Client avec les bons paramètres (URL de l'API, clé privé, préfixe de préprod)
- fournir des méthodes pour s'authentifier (OAuth, Restfull)
- fournir des méthodes métiers qui appellent l'API (paiement, etc.)
- construire les paramètres spécifiques de la requête HTTP (URL, verbe HTTP)
- faire l'appel à l'API via CURL ou PHP avec les bonnes options et paramètres


#### Analyse du code 


La propriété `CP`  n'est pas assez explicite. Il faut avoir le contexte pour comprendre qu'il s'agit de la clé privé, et on attendrait a minima que la variable soit nommé en anglais. De même, pour la propriété `UI`.

La méthode `isConfigured` devrait être appelé avant l'utilisation du Client. Ca permettrait de vérifier qu'on a bien des valeurs nécessaires à son utilisation.

La méthode `requestApi` prend comme paramètre une chaîne de caractère, qui est ensuite transformée pour appeler une autre méthode. Ceci me semble être une mauvaise pratique car cela induit une convention qui n'est pas documenté, pas testé et qui rend dangereux l'extension / modification du code. 
Cela permet d'éviter des gros switch/case pour construire la partie spécifique de la requête HTTP qu'on est en train de construire. Mais c'est trop fragile et peu testé pour être viable. Le risque c'est d'avoir une Fatal Error sur le Client car la méthode appelée n'existe pas.

La méthode `getAccountInfos` semble beaucoup trop grande. Il faudrait la refactorer. 
Il y a des vérifications faites sur plusieurs valeurs renvoyées par l'API. Il faudrait a minima avoir une méthode par valeur, pour clarifier le contrat de validité de chaque valeur. 
Il semble aussi possible d'extraire une fonction générique pour l'appel API et la vérification d'existence d'une erreur. 
Les tests `if ($account == false)` et les `return false` ne semble pas correspondre au contrat de retour des méthodes `requestApi` (qui renvoie du json mais a priori pas de valeur booléenne) et de la méthode `getAccountInfos` (qui elle aussi doit renvoyer du json). 
Il faut modifier le contrat décrit dans la documentation, sinon quel est son intérêt ?












## SECONDE ETAPE : TA PROPOSITION D'AMÉLIORATION

Après ton analyse personnelle, à toi de nous montrer comment tu aurais codé ce SDK. Fais-nous une proposition, montre-nous comment tu codes ! Aucune règle imposée, on ne limite pas le nombre de fichiers ou le nombre de fonctions : on veut simplement un SDK facile à utiliser pour tous nos confrères dev.
Base-toi sur la documentation PayGreen : https://paygreen.fr/documentation/api-documentation-categorie?cat=paiement
Tu devrais te concentrer sur l'intégration des API PayGreen (le OAuth n'est pas requis). Tu a le droit de t'inspirer d'autres SDK, le but étant de simplifier l'usage de nos APIs.
Voici des élément de connexion API:

    Host : https://preprod.paygreen.fr
    Identifiant : f3d64445bb5229c50c1b8c95760686ae
    Clé privé : 095d-4211-96bd-987cb9f4a695 Ce SDK devrait donc implémenter la connexion RestFull ainsi que la création d'une transaction et sa récupération. À travers ce SDK, montre-nous toute ta logique !

Enjoy ;)

