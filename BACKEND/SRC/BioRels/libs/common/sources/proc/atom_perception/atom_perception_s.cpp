#include "headers/proc/atomperception.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/logger.h"
const std::vector<protspace::AtomRule> protspace::AtomPerception::sSulfurRules={
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE}},"S.3",true,false,0},///006:S50
    {16,{{6,BOND::DOUBLE}},"S.2",false,false,0},///0FI:S2
    {16,{{16,BOND::SINGLE}},"S.2",false,false,-1},///0FI:S2
    {16,{{15,BOND::DOUBLE}},"S.2",false,false,0},///TSE:S1P
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE}},"S.2",true,true,0},///00N:S1X
    {16,{{7,BOND::SINGLE},{7,BOND::SINGLE}},"S.2",true,true,0},///TIM:S9
    {16,{{1,BOND::SINGLE},{6,BOND::SINGLE}},"S.3",false,false,0},///02G:S
    {16,{{1,BOND::SINGLE},{15,BOND::SINGLE}},"S.3",false,false,0},///06S:S
    {16,{{6,BOND::SINGLE},{7,BOND::SINGLE}},"S.2",true,true,0},///0DQ:S30
    {16,{{6,BOND::SINGLE},{16,BOND::SINGLE}},"S.3",false,false,0},///0AM:S1/S2
    {16,{{6,BOND::SINGLE},{16,BOND::SINGLE}},"S.3",true,false,0},///TS2:SG5
    {16,{{16,BOND::SINGLE},{16,BOND::SINGLE}},"S.3",false,false,0},///TSY:S1
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///07T:SAY
    {16,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE}},"S.2",true,true,0},///0F2:S1
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"S.3",true,false,0},///0H2:S5
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE}},"S.3",true,false,0},///1CL
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{47,BOND::SINGLE}},"S.3",true,false,0},///0KA:S1/S3/S4
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"S.3",true,false,0},///0KA:S2
    {16,{{6,BOND::SINGLE},{29,BOND::SINGLE}},"S.3",true,false,0},///0TE:S1
    {16,{{6,BOND::SINGLE},{15,BOND::SINGLE}},"S.3",false,false,0},///112:S1G
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///2TG:S1
    {16,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"S.3",false,false,0},///313:S
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::DOUBLE}},"S.2",false,false,0},///3F1:S12
    {16,{{6,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"S.3",true,false,0},///402:S1/S2
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{42,BOND::SINGLE}},"S.3",true,false,0},///CFM:S4B
    {16,{{6,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///K1R:SAG
    {16,{{1,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///CSX:SG
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///CSX:SG
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"S.3",true,false,1},///DSK
    {16,{{6,BOND::SINGLE},{6,BOND::DOUBLE}},"S.2",true,true,1},///CZ6
    {16,{{6,BOND::SINGLE},{6,BOND::DOUBLE}},"S.2",true,false,1},///MBT
    {16,{{6,BOND::DOUBLE},{6,BOND::DOUBLE}},"S.2",true,true,0},///V78
    {16,{{6,BOND::DOUBLE},{7,BOND::DOUBLE}},"S.2",true,true,0},///V21
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{28,BOND::SINGLE}},"S.3",true,false,0},///B51
    {16,{{6,BOND::SINGLE},{6,BOND::SINGLE},{46,BOND::SINGLE}},"S.3",true,false,0},///SXC
    {16,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::DOUBLE}},"S.O",false,false,0},///BSC
    {16,{{6,BOND::DOUBLE},{76,BOND::SINGLE}},"S.2",true,false,0},///ELJ
    {16,{{6,BOND::DOUBLE},{78,BOND::SINGLE}},"S.2",false,false,0},///2PT
    {16,{{6,BOND::DOUBLE},{78,BOND::SINGLE}},"S.2",true,false,0},///4KV
    {16,{{1,BOND::SINGLE},{6,BOND::TRIPLE}},"S.2",false,false,0},///OSV
    {16,{{8,BOND::DOUBLE},{8,BOND::DOUBLE}},"S.o2",false,false,0},///SO2
    {16,{{8,BOND::DOUBLE}},"S.O",false,false,0},///SX
    {16,{{8,BOND::SINGLE},{8,BOND::SINGLE},{8,BOND::DOUBLE}},"S.o2",false,false,0},///SO3
    {16,{{16,BOND::SINGLE},{16,BOND::SINGLE},{16,BOND::DOUBLE}},"S.2",false,false,0},///S4H
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{28,BOND::SINGLE}},"S.3",true,false,0},///82N:S1/S4
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"S.3",true,false,0},///CLF
    {16,{{6,BOND::SINGLE},{9,BOND::SINGLE},{9,BOND::SINGLE},{9,BOND::SINGLE},{9,BOND::SINGLE},{9,BOND::SINGLE}},"S.3",false,false,0},///D65
    {16,{{26,BOND::SINGLE},{26,BOND::SINGLE},{23,BOND::SINGLE}},"S.3",true,false,-1},///8P8
    {16,{{6,BOND::DOUBLE},{29,BOND::SINGLE}},"S.2",true,false,0},///8ZR

};

bool protspace::AtomPerception::processSulfur(MMAtom& atom)
{
    try{
        if (isSulfonamide(atom))return true;
        if (mGroupPerception &&isSulfonate(atom))return true;
        if (isSulfone(atom))return true;
        return isOtherSulf(atom);
        //cout << nC<<" " << nN<< " " << nOx<<" " << nHy<<endl;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::processSulfur");
        throw;
    }

}
bool protspace::AtomPerception::isSulfonamide(MMAtom& atom)
{
    try{
        if (!(mAtStat.numOx()==2  &&
              mAtStat.numBd()==4 &&
              ((mAtStat.numN()==1 && mAtStat.numC()==1)
               || (mAtStat.numN()==2)
               )) &&
                !(mAtStat.numN()==1 && mAtStat.numOx()==3
                  && mAtStat.numBd()==4 && mAtStat.numDouble()==2 && mAtStat.numSing()==2))return false;//Case 0UQ:S1
        setMOL2(atom,"S.o2");/// Case 0W0:N22
        //   std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"SULPHONAMIDE"<<std::endl;
        size_t nOx=0;
        for (size_t iAtm2 =0; iAtm2 <mAtStat.numBd(); ++iAtm2)
        {
            MMAtom& atomL1 = atom.getAtom(iAtm2);
            MMBond& bondL1=atom.getBond(iAtm2);
            if (atomL1.isOxygen())
            {
                //std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"SULPHONAMIDE"<<std::endl;
                setMOL2(atomL1,(nOx<=2?"O.2":"O.3"));
                bondL1.setBondType(BOND::DOUBLE);
            }
            else if (atomL1.isNitrogen())
            {
                //std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"SULPHONAMIDE"<<std::endl;
                setMOL2(atomL1,"N.pl3");
                bondL1.setBondType(BOND::SINGLE);
            }
            else bondL1.setBondType(BOND::SINGLE);
        }
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isSulfonamide");
        throw;
    }
}

bool protspace::AtomPerception::isSulfonate(MMAtom& atom)
{
    try{
        if (!(mAtStat.numOx()>=3 && mAtStat.numBd()==4))return false;
        //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"SULPHONATE"<<std::endl;
        setMOL2(atom,"S.o2");
        for (size_t iAtm2 =0; iAtm2< mAtStat.numBd(); ++iAtm2)
        {
            MMAtom& atomL1 = atom.getAtom(iAtm2);
            MMBond& bondL1=atom.getBond(iAtm2);
            if (atomL1.isOxygen())
            {
                //std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"SULPHONATE"<<std::endl;
                setMOL2(atomL1,"O.co2");
                bondL1.setBondType(BOND::AROMATIC_BD);
            }
            else bondL1.setBondType(BOND::SINGLE);
        }
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isSulfonate");
        throw;
    }
}

bool protspace::AtomPerception::isSulfone(MMAtom& atom)
{
    try{
        if (!(mAtStat.numOx()==2 && mAtStat.numN()==0 && (mAtStat.numBd()==3||mAtStat.numBd()==4)))return false;
        //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"SULPHONE"<<std::endl;
        setMOL2(atom,(mAtStat.numBd()==4)?"S.o2":"S.O");

        for (size_t iAtm2 =0; iAtm2 < mAtStat.numBd(); ++iAtm2)
        {
            MMAtom& atomL1 = atom.getAtom(iAtm2);
            MMBond& bondL1=atom.getBond(iAtm2);
            if (atomL1.isOxygen())
            {
                //    std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"SULPHONE"<<std::endl;
                setMOL2(atomL1,"O.2");
                bondL1.setBondType(BOND::DOUBLE);
            }
            else  bondL1.setBondType(BOND::SINGLE);
        }
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isSulfone");
        throw;
    }
}

bool protspace::AtomPerception::isOtherSulf(MMAtom& atom)
{

    try{
        if (((mAtStat.numBond()==2 && mAtStat.numSing()==2)
             ||(mAtStat.numBond()==4 && mAtStat.numSing()==4))
                && !mAtStat.isArRing())
        {  setMOL2(atom,"S.3");return true;   }
        if (!mAtStat.isRing() && mAtStat.numBd()==1 && mAtStat.numDouble()==1
                && mAtStat.numOx()==0)
        {
            setMOL2(atom,"S.2");return true;
        }
        if (!mAtStat.isRing() && mAtStat.numBd()==1 && mAtStat.numSing()==1 &&
                mAtStat.numOx()==0 && mAtStat.numHy()==0)
        {
            setMOL2(atom,"S.3");atom.setFormalCharge(-1);return true;}
        if (!mAtStat.isRing() && mAtStat.numBd()==4 && mAtStat.numSing()==2 &&mAtStat.numDouble()==2)
        {
            setMOL2(atom,"S.2");return true;
        }

        size_t pos=0;
        for(const AtomRule& entry:sSulfurRules)
        {
            ++pos;
            ++mAllCounts;
            if (!followRule(entry,atom))continue;
            setMOL2(atom,entry.mMOL2);
            return true;
        }


        /// Manual correction to apply to IZ3
        /// Ignore R1A
        ///3164027 covered by 0US

        LOG_ERR("Unrecognized atom "+atom.getParent().getName()+" - "+atom.getIdentifier()+" "+atom.getAtomicName());
        atom.setMOL2Type("Du");
        return false;


    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::perceiveSingleCarbon");
        throw;
    }

}


