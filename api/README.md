# ADISE25_GeorgePerakis

Game Flow (PS_requests Order)

The following commands must be executed in order.
The relevant curls are in the PS_requests.txt. 
These commands are made for PowerShell.

1. Login
Authenticate the player.

2. Make Game
Create a new game session.

3. Join Game
Players join the created game.

4. Deal Cards
Deal the initial cards to the players.

5. Play Cards
Players play cards according to the game rules.

6. Deal Cards (if hands are empty)
If all players’ hands are empty, new cards are dealt.

7. Repeat Steps 5–6
Continue playing cards and dealing new cards when needed.

8. Game Over
The process continues until the game-ending condition is met.

The project utilizes mysql, and the schema will be provided