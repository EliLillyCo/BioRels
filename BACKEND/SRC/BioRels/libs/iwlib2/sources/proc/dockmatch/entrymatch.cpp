#include "headers/proc/dockmatch/entrymatch.h"
using namespace protspace;


EntryMatch::EntryMatch(MMAtom& atomR,MMAtom& atomC):atomR(atomR),atomC(atomC) {}
EntryMatch::EntryMatch(const EntryMatch& m):atomR(m.atomR),atomC(m.atomC){}
bool EntryMatch::operator==(const EntryMatch& m)
{
    return (&atomR==&m.atomR && &atomC==&m.atomC);

}
bool EntryMatch::operator!=(const EntryMatch& m)
{
    return (&atomR!=&m.atomR || &atomC!=&m.atomC);

}
