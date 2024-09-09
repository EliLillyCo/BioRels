#ifndef ATOMSTAT_H
#define ATOMSTAT_H

namespace protspace
{
class MMAtom;
class AtomStat
{
protected:
    MMAtom* mCurrAtom;
    void clear();
    unsigned char nBond;
    bool isInArRing;
    bool isInRing;

    unsigned char nOx;
    unsigned char nN;
    unsigned char nC;
    unsigned char nHy;
unsigned char nHeavy;
    unsigned char nSing,nDouble,nTrip,nAr,nDe,nAmide;
    unsigned char mAtomicNum;
    unsigned short residue_type;
    void getStatBond();
    void getStatAtom();
public:
    AtomStat();
    AtomStat(protspace::MMAtom& pAtom);
    void updateAtom(MMAtom& pAtom);
    const bool& isRing()const{return isInRing;}
    const bool& isArRing()const{return isInArRing;}
    const unsigned char& numOx()const {return nOx;}
    const unsigned char& numN()const {return nN;}
    const unsigned char& numC()const {return nC;}
    const unsigned char& numHy()const {return nHy;}
    const unsigned char& numBd()const {return nBond;}
    const unsigned char& numSing()const {return nSing;}
    const unsigned char& numDouble()const {return nDouble;}
    const unsigned char& numTriple()const {return nTrip;}
    const unsigned char& numAr()const {return nAr;}
    const unsigned char& numDe()const {return nDe;}
    const unsigned char& numAmide()const {return nAmide;}
    const unsigned char& numBond()const {return nBond;}
    const unsigned short& resType()const{return residue_type;}
    const unsigned char&numHeavy()const{return nHeavy;}

};
}
#endif // ATOMSTAT_H

