#ifndef ENTRYMATCH_H
#define ENTRYMATCH_H

#include "headers/molecule/mmatom.h"
namespace protspace
{
class EntryMatch
{
public:
    MMAtom& atomR;
    MMAtom& atomC;

public:
    EntryMatch(MMAtom& atomR,MMAtom& atomC);
    EntryMatch(const EntryMatch& m);
   // EntryMatch& operator =  ( EntryMatch& other);
    bool operator==(const EntryMatch& m);
    bool operator!=(const EntryMatch& m);
};
}
#endif // ENTRYMATCH_H
