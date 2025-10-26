# Plugin VigiEau

Ce plugin permet de remonter les informations du site de l'information sécheresse du Gouvernement [**VigiEau**](https://vigieau.gouv.fr/) via l'API du site https://api.vigieau.beta.gouv.fr/swagger/.

## Installation
1. Le plugin s'installe comme n'importe quel autre plugin sur Jeedom via le Market.<br/>

## Configuration
1. Une fois installé et activé, sur la page de configuration, vous pouvez définir l'heure à laquelle le plugin ira chercher les information.<br/>
<img width="861" height="135" alt="image" src="https://github.com/user-attachments/assets/723acfaa-c53f-48fc-a89f-e710619f77d6" /></br>

2. Lancer le plugin qui se trouve dans la catégorie Météo.<br/>
<img width="408" height="183" alt="image" src="https://github.com/user-attachments/assets/8049d2dd-a1e3-4486-a1b1-3ace66c58238" /></br>

3. Ajouter un équipement, comme n'importe quel équipement sous Jeedom.

5. Configurer les paramètres généraux, puis dans les oaramètres spécifiques :
   - Indiquer si vous souhaitez utiliser le widget développé pour le plugin.</br>
   - Indiquer si vous souhaitez aavoir un dimensionnement adaptatif du widget.</br>
   - Indiquer votre profil de consommation (Particulier/Entreprise/Collectivités/Exploitation agricoles).</br>
   - Indiquer le type d'eau consommé (Du robinet/D'un cours d'eau ou d'une rivière/Des nappes (puits ou forage)).</br>
   - Indiquer le(s) type(s) d'usage que vous souhaitez afficher.</br>

![image](/docs/images/VigiEau_Conf.png)</br>

6. Sauvegarder.

> [!NOTE]
>Type d'eau consommé :
>| Type | Description |
>| --- | --- |
>| Du robinet/Eau potable | L’eau potable provient des nappes et des cours d'eau. Elle est traitée en plusieurs étapes afin de lui donner la qualité de l’eau potable. Elle est ensuite acheminée jusqu’à votre domicile. |
>| D'un cours d'eau ou d'une rivière/Eau superficielle | Il s'agit pour l'essentiel des cours d'eau, des lacs et des eaux de ruissellement. Vous êtes concernés si vous prélevez directement dans un cours d’eau. |
>| Des nappes (puits ou forage)/Eau souterraine | Ce sont toutes les eaux se trouvant sous la surface du sol : les nappes phréatiques, nappes profondes, etc. Vous utilisez cette eau, si vous disposez d’un puits ou d’un forage. |
<!--
>
>Type éditorial :
>| Type | Description |
>| --- | --- |
>| Particulier | Utilisation de l'eau à titre personnel (arrosage du jardin, des fleurs, piscine...) |
>| Profesionnel | Utilisation de l'eau à titre profesionnel (agriculteur...) |

## Pour aller plus loin
Dans vos scénarios, vous pouvez utiliser comme déclencheur la commande "Niveau restriction zone SUP" et/ou "Niveau restriction zone SOU".
-->

## Widget
Il est composé de 2 ou 4 parties.
| Partie | Description |
| --- | --- |
| 1ère partie | La commune / Les arrêtés téléchargeables / Dates de début et fin de l'arrêté |
| 2ème partie | Vous avez sélectionné qu'un seul type d'eau : Les informations concernant le type d'eau consommée. |
| 2ème - 4ème partie | Vous avez sélectionné tous les types d'eau consommée : Vous affichez donc les informations sur les 3 types d'eau consommée. |

Dans les pavés "Type d'eau consommée", vous retrouvez les informations.
| Partie | Description |
| --- | --- |
| Titre | Le type d'eau consommée |
| Nom | Nom de la zone d'alerte |
| Restriction | Le type de restriction : Pas de restrictions / Vigilance / Alerte / Alerte renforcée / Crise |
| Mesures | Les différentes mesures en lien avec les restrictions |
