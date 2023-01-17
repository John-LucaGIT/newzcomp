# About
NewzComp was created in December of 2020 to combat modern media bias through comparative news articles which give readers all the information and resources necessary to form an informed opinion of their own.

## NewzComp.com
NewzComp primarily active on social media apps, most successfully on instagram. The idea was to build presence via social media and later merge to web and mobile apps.
NewzComp.com was created in PHP frontend templates were used with Bootstrap and Mobirise. Due to limited time the website never received much attention, towards the end of the project more focus went into some functionailty such as creating user accounts, subscribing to newsletters and managing access as well as some statistical insights for administrators. A admin control pannel was being built with the goal to easily publish news articles in our style to the site for all our reporters. Development halted when NewzComp came to an end.

## Statistics & Bot detection
NewzComp.com featured some statistics, this included daily visitors, total visitors, unique visitors and bots visiting the site. The visitors were displayed on a map so that team members could see where NewzComp was most prominantly searched. This would be helpful to determine our target audience.

## Why not Google Analytics
I did integrate google analytics and some other service providers to provide similar viewer statistics however, I found that the information was not very reliable and I did not understand the methods used to project the insights I received. I wanted more control over the statistics of the page and I wanted to know how many bots were visiting, something google did not provide.

## Impact of tracking methods used on NewzComp.com
As you can probably tell if you visited NewzComp.com or looked at some code examples I provided, the bot detection methods while somewhat useful result in a big negative impact in time complexity. I am fully aware that the methods of calculating and collecting statistical information on the site is less than sub-optimal with huge database queries and nested loops. Since the website never received much traffic and development was halted I never addressed these issues, other than implementing quick caching methods with Cloudflare.


## Why was NewzComp.com thrown together?
If you look at the source code for NewzComp.com you may realize it is a combination of different scripts, tempaltes and independent code. The reason this site was thrown together is due to the very limited time I had when running NewzComp. The website was never a priority as almost all of our viewers only used Instagram. The sole purpose of the website was to answer some FAQ's and present our goals and vision to stakeholders and potential investors.

## What would I change?
If I were to start over I would not use Mobirise and PHP, I would try using some modern web frameworks, either Vue.js or potentially React. Vue uses server-side rendering so React may be better but I have used Vue in the past and I like its simplicity, it may present some challanges if NewzComp were to expand to a multi-page web app as was planned. Using Vue in other projects has made me realize how slow PHP is, relying on SQL queries is painful when it comes to any user interaction and quick load times.

## You need to fix this!
Please let me know if you find any serious security problems in the code I have shared. Posting this to github is a risk as the site is live, however given the basically non-existant registered user count I decided to share my code, besides I hope that someone who actually looks at the code shares the faults with me rather than exploits them.

## Contact
You can reach me here:
[info@newzcomp.com](mailto:info@newzcomp.com)

