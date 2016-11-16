freepbx-winner_winner
=====================

Partially finished FreePBX Module to set up radio call-in style contests, where caller number `X` wins.

2013-06-17
This module is completely non-functional at present. 

2016-11-16
Three years later and it is possible this will never be finished. For anyone who wants to get this working in FreePBX, add the following lines to `/etc/asterisk/extesions_custom.conf`:

```
[app-wwinner-reset]
exten => s,1(reset),Set(GLOBAL(CONCOUNT)=0)
exten => s,n,Set(GLOBAL(CONWIN)=10)
exten => s,n,Read(GLOBAL(CONWIN),please-enter-the&count&then-press-pound,,,,)
exten => s,n,Noop(WinnerWinner: Contest counter reset to ${CONCOUNT} winning number is set to ${CONWIN})
exten => s,n,Playback(count&is-set-to)
exten => s,n,SayNumber(${CONWIN},f)
exten => s,n,Playback(goodbye)
exten => s,n,Return
;--== end of [app-wwinner-reset] ==--;


[app-wwinner]
exten => s,1,Set(foo=${SET(GLOBAL(CONCOUNT)=${MATH(${CONCOUNT} + 1,i)})})
exten => s,n,Noop(WinnerWinner: Contest caller number ${foo} of ${CONWIN})
exten => s,n,Wait(1)
exten => s,n,GotoIf($["${foo}"="${CONWIN}"]?winner)
exten => s,n,Playback(im-sorry&you-are-caller-num)
exten => s,n,SayNumber(${foo},f)
exten => s,n,Playback(goodbye)
exten => s,n,Macro(hangupcall,)
exten => s,n(winner),Macro(user-callerid,)
exten => s,n,Noop(WinnerWinner: Caller ${AMPUSER} is a winner!)
exten => s,n,Playback(you-are-caller-num)
exten => s,n,SayDigits(${foo})
exten => s,n,Playback(one-moment-please)
exten => s,n,Return
;--== end of [app-wwinner] ==--;
```

Once the above lines are in place, you need to create two Custom Destinations as follows:

reset: `app-wwinner-reset,s,1` select return and set to terminate call

winner: `app-wwiner,s,1` select return, and set a destination where the winning caller goes.

The first Custom destination is for resetting the caller count back to zero and for setting what number is used for the winner. You would create a Misc. Application to this destination so that dialing a feature code will reset the counter and use it before each new contest starts. The second Custom Destination is where you route inbound callers.
