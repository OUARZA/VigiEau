# Plugin Propluvia

Ce plugin permet de remonter les informations du site de l'information sécheresse du Gouvernement [**Propluvia**](https://propluvia.developpement-durable.gouv.fr/propluviapublic/recherche-particulier) via l'API du site https://eau.api.agriculture.gouv.fr.

## Installation
1. Le plugin s'installe comme n'importe quel autre plugin sur Jeedom via le Market.<br/>

## Configuration
1. Une fois installé et activé, sur la page de configuration, vous pouvez définir l'heure à laquelle le plugin ira chercher les information.<br/>
![image](https://github.com/OUARZA/Propluvia/assets/34892335/84857d98-5694-40d4-ad00-e04770220738)

2. Lancer le plugin qui se trouve dans la catégorie Météo.<br/>
![image](https://github.com/OUARZA/Propluvia/assets/34892335/0db2b09b-a0c6-48fc-99f1-9fa58c3ad5da)

3. Ajouter un équipement, comme n'importe quel équipement sous Jeedom.  
4. Configurer les paramètres généraux, puis dans les aramètres spécifiques, indiquer le code INSEE de la commune que vous souhaitez consulter. Indiquer quels types de restrictions vous souhaitez suivre (Eaux superficielles, Eaux sonterraines), ainsi que le type éditorial (Particulier, Profesionnel).  
![image](https://github.com/OUARZA/Propluvia/assets/34892335/ddf81407-3b43-45c3-b67f-301f38e4e514)

>**NOTE**  
>Restrictions spécifiques :  
>| Type | Description |
>| --- | --- |
>| Eaux superficielles | Les eaux superficielles ou eaux de surface regroupent les eaux des pluies, des sources et du ruissellement d'autres sources d'eau. Les eaux de surface cheminent toutes afin d'arriver à un plus grand plan d'eau, pour exemple, les rivières qui se jettent dans l'océan.[<sup>[1]</sup>](#notes-et-références) |
>| Eaux souterraines | Les eaux souterraines peuvent être résumées comme étant l'ensemble des eaux stockées en profondeur ou en dessous de la surface terrestre, saturant complètement les pores du sous-sol.[<sup>[2]</sup>](#notes-et-références) |
>
>Type éditorial :  
>| Type | Description |
>| --- | --- |
>| Particulier | Utilisation de l'eau à titre personnel (arrosage du jardin, des fleurs, piscine...) |
>| Profesionnel | Utilisation de l'eau à titre profesionnel (agriculteur...) |

5. Sauvegarder.

## Pour aller plus loin
Dans vos scénarios, vous pouvez utiliser comme déclencheur la commande "Niveau restriction zone SUP" et/ou "Niveau restriction zone SOU".


## Notes et références
[1] : https://www.projetecolo.com/eaux-de-surface-definition-et-exemples-733.html  
[2] : https://www.projetecolo.com/eaux-souterraines-definition-caracteristiques-formation-et-importance-601.html
