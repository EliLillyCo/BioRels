#include "headers/proc/atomstat.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/molecule/mmring.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#undef NDEBUG /// Active assertion in release
protspace::AtomStat::AtomStat()
{
    clear();
}
protspace::AtomStat::AtomStat(protspace::MMAtom& pAtom):mCurrAtom(&pAtom)
{

updateAtom(pAtom);
}


void protspace::AtomStat::updateAtom(protspace::MMAtom &pAtom)
try{
    clear();

    mCurrAtom=&pAtom;
        nBond=pAtom.numBonds();
        isInArRing=false;
        isInRing=false;
        for (size_t iR=0;iR<pAtom.getParent().numRings();++iR)
        {
            const protspace::MMRing& ring=pAtom.getParent().getRing(iR);
            if (!ring.isInRing(pAtom)) continue;
            if (ring.isAromatic())isInArRing=true;
            isInRing=true;
        }
        residue_type= pAtom.getResidue().getResType();
        mAtomicNum=pAtom.getAtomicNum();
        getStatAtom();
        getStatBond();
}catch(ProtExcept &e)
{

    /// Ring should be in boundaries
    assert(e.getId()!="352501");
    assert(e.getId()!="310701");/// Residue should exists
    e.addHierarchy("AtomStat:updateAtom");
    e.addDescription(pAtom.toString());
    throw;
}


void protspace::AtomStat::clear()
{
    mCurrAtom=nullptr;
    nBond=0;
    isInArRing=false;
    isInRing=false;

    nHeavy=0;
    nOx=0;
    nN=0;
    nC=0;
    nHy=0;
    nSing=0;nDouble=0;nTrip=0;nAr=0;nDe=0;nAmide=0;
    mAtomicNum=0;
    residue_type=0;
}



void protspace::AtomStat::getStatBond()
try{
    nSing=0;nDouble=0;nTrip=0;nAr=0;nDe=0;nAmide=0;
    for(size_t iAtm=0;iAtm < mCurrAtom->numBonds();++iAtm)
    {
        const unsigned short& btype=mCurrAtom->getBond(iAtm).getType();
        switch (btype)
        {
        case BOND::SINGLE: nSing++;break;
        case BOND::DOUBLE: nDouble++;break;
        case BOND::TRIPLE: nTrip++;break;
        case BOND::AROMATIC_BD: nAr++;break;
        case BOND::DELOCALIZED: nDe++;break;
        case BOND::AMIDE: nAmide++;break;
            default: break;
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="310201" && e.getId()!="071001");  /// Bond should be in boundaries
    e.addHierarchy("AtomStat:getStatBond");
    throw;
}


void  protspace::AtomStat::getStatAtom()
try{
    nC=0;
    nOx=0;
    nHy=0;
    nN=0;
    nHeavy=0;
    for (size_t iAtm2 =0; iAtm2 < mCurrAtom->numBonds(); ++iAtm2)
    {
        const unsigned char& atomL = mCurrAtom->getAtom(iAtm2).getAtomicNum();
        if (atomL>1)nHeavy++;
        if (atomL==6) nC++;
        else if (atomL==7) nN++;
        else if (atomL==8) nOx++;
        else if (atomL==1) nHy++;
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="310501");  /// Atom should be in boundaries
    e.addHierarchy("AtomStat:getStatAtom");
    throw;
}



