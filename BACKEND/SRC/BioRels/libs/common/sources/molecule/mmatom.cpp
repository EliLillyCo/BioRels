#include <fstream>
#include <iomanip>
#include <sstream>
#include "headers/molecule/mmatom.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/mol2data.h"
#include "headers/statics/strutils.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/protpool.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
protspace::MMAtom::MMAtom(const unsigned int& id, MacroMole * const molecule):
    protspace::dot<protspace::MacroMole,protspace::MMAtom>(molecule,molecule->numAtoms(),id),
    mPosition(protspace::ProtPool::Instance().coord.acquireObject(mCoordPoolPos)),
    mName(""),
    mMOL2type("Du"),
    mBFactor(0),
    mFCharge(0),
    mAtomicNumber(DUMMY_ATM),
    mResidueId(-2),
    mSelected(true),
    mProperties()
{
    mPosition.clear();
}


protspace::MMAtom::MMAtom(const MMAtom& atom):
    protspace::dot<MacroMole,MMAtom>(atom.mParent,
                                     atom.mParent->numAtoms(),
                                     atom.mFId),
    mPosition(protspace::ProtPool::Instance().coord.acquireObject(mCoordPoolPos)),
    mName(atom.mName),
    mMOL2type(atom.mMOL2type),
    mBFactor(atom.mBFactor),
    mFCharge(atom.mFCharge),
    mAtomicNumber(atom.mAtomicNumber),
    mResidueId(atom.mResidueId),
    mSelected(atom.mSelected),
    mProperties(atom.mProperties)
{

    mPosition=atom.mPosition;
}





const double& protspace::MMAtom::getvdWRadius() const
{
    assert(mAtomicNumber<NNATM);
    return Periodic[mAtomicNumber].vdw;
}





const double& protspace::MMAtom::getWeigth() const
{
    assert(mAtomicNumber<NNATM);
    return Periodic[mAtomicNumber].weigth;
}


const std::string& protspace::MMAtom::getAtomicName() const
{
    assert(mAtomicNumber<NNATM);
    return Periodic[mAtomicNumber].fullname;
}

bool protspace::MMAtom::isMetallic() const
{
    return (mAtomicNumber >= 25 && mAtomicNumber <= 30 && mAtomicNumber != 27)
            ||mAtomicNumber==12;
}

bool protspace::MMAtom::isHalogen()const
{
    return mAtomicNumber == 9 || mAtomicNumber == 17 || mAtomicNumber == 35
            ||mAtomicNumber == 53;
}

bool protspace::MMAtom::isIon()const
{
    return mAtomicNumber == 3 || mAtomicNumber == 11 || mAtomicNumber == 19;
}

std::string protspace::MMAtom::getElement()const
{

    assert(mAtomicNumber<NNATM);
    return Periodic[mAtomicNumber].name;
}


bool protspace::MMAtom::isBioRelevant() const
{
    assert(mAtomicNumber<NNATM);
    return Periodic[mAtomicNumber].isBioRelevant;
}


protspace::MMAtom::~MMAtom()
{
    try{
    protspace::ProtPool::Instance().coord.releaseObject(mCoordPoolPos);
}catch(ProtExcept &e)
    {std::cerr<<e.toString()<<std::endl;}
}

void protspace::MMAtom::setAtomicType(const unsigned char& atomicNum) throw(ProtExcept)
{
    mAtomicNumber=atomicNum;
}





void protspace::MMAtom::setAtomicType(const std::string& atomicName) throw(ProtExcept)
{
    std::string trimmed(removeSpaces(atomicName));
    const size_t len(trimmed.length());
       if (len ==0 || len>3)
           throw_line("310101",
               "protspace::MMAtom::setAtomicName",
               "Given atomic name must have 1,2 or 3 characters :"+
               atomicName) ;
    // First scan - case sensitive :

        if (trimmed=="LP")trimmed="Du";
    if (trimmed=="D")trimmed="H";
    else if (trimmed=="X"){mAtomicNumber=DUMMY_ATM;return;}

    mAtomicNumber=nameToNum(trimmed);
    if (mAtomicNumber!= NNATM_OUT) return;

    // Nothing found. Trying all lowercase to do a 'case insensitive'
    toLowercase(trimmed);
    mAtomicNumber=nameToNum(trimmed);
    if (mAtomicNumber!= NNATM_OUT) return;

    static const int diff='A'-'a';
    trimmed[0]+=diff;
    mAtomicNumber=nameToNum(trimmed);
    if (mAtomicNumber!= NNATM_OUT) return;
    throw_line("310102",
               "protspace::MMAtom::setAtomicName",
               "Atomic name not found : "+atomicName+"/"+trimmed) ;
}










void protspace::MMAtom::select(const bool& isSelected,
                    const bool& applyRes,
                    const bool& applyToBond)
{
    mSelected=isSelected;
    if (applyToBond)
        for (size_t iBd=0; iBd < mListLinks.size(); ++iBd)
        {
            MMBond& bond = mParent->getBond((const size_t &) mListLinks.at(iBd));
            if ((isSelected && bond.getDot1().isSelected()
                 && bond.getDot2().isSelected())
                    || !isSelected)
            {
                bond.setUse(isSelected);
            }

        }
    if (applyRes) getResidue().checkUse();


}


void protspace::MMAtom::serialize(std::ofstream &out) const
{
    // READING ID
    out.write((char*)&mFId,sizeof(mFId));
    // READING ID
    out.write((char*)&mMId,sizeof(mMId));

    double v=mPosition.getX();
    out.write(reinterpret_cast<char*>(&v),sizeof(double));
    v=mPosition.getY();
    out.write(reinterpret_cast<char*>(&v),sizeof(double));
    v=mPosition.getZ();
    out.write(reinterpret_cast<char*>(&v),sizeof(double));

    size_t length=mName.size();
    out.write((char*)&length,sizeof(size_t));
    out.write(mName.c_str(),mName.size());
    length=mMOL2type.size();
    out.write((char*)&length,sizeof(size_t));
    out.write(mMOL2type.c_str(),mMOL2type.size());


    v=mBFactor;
    out.write(reinterpret_cast<char*>(&v),sizeof(double));

    out.write((char*)(&mFCharge),sizeof(unsigned char));
    out.write((char*)(&mAtomicNumber),sizeof(unsigned char));
    out.write((char*)(&mResidueId),sizeof(int));
    out.write((char*)(&mSelected),sizeof(bool));
    out.write((char*)(&mProperties.getPropValue()),sizeof(uint16_t));
}

void protspace::MMAtom::unserialize(std::ifstream& ifs)
{
    // READING ID of molecule
    ifs.read((char*)&mFId,sizeof(int));
    ifs.read((char*)&mMId,sizeof(int));

    ifs.read((char*)&mPosition.x(),sizeof(double));
    ifs.read((char*)&mPosition.y(),sizeof(double));
    ifs.read((char*)&mPosition.z(),sizeof(double));
    readSerializedString(ifs,mName);
    readSerializedString(ifs,mMOL2type);
    ifs.read((char*)&mBFactor,sizeof(double));
    ifs.read((char*)&mFCharge,sizeof(unsigned char));
    ifs.read((char*)&mAtomicNumber,sizeof(unsigned char));
    ifs.read((char*)&mResidueId,sizeof(int));

    ifs.read((char*)&mSelected,sizeof(bool));
    uint16_t t=0x0000;
    ifs.read((char*)&t,sizeof(uint16_t));
    mProperties.addProperty(t);


}


std::string protspace::MMAtom::toString(const bool& onlyUsed, const bool &withBond)const
{
    std::ostringstream oss;
    oss << "--- ATOM " << mName
        << "("<<mFId
        << "/"<<mMId<<") "
        << mMOL2type<< " --"
        << mParent->getName()<<"--";
    oss << "(" << (static_cast<unsigned>(getAtomicNum()))
        << ":" << getElement()
        << ")--"<<mPosition<<"--";

    oss << "FCharge: "<< (signed)mFCharge
        << "--"  << getResidue().getChainName()
        << "::"             << getResidue().getName()
        << "("             << getResidue().getFID()
        << "/"              << getResidue().getMID()<<") ";
    oss <<((mSelected)?"T ":"F ");
    oss << "--"<<mListDots.size()<<"/"<<mListLinks.size()<<"\n";
    if (!withBond)return oss.str();
    for (size_t i=0;i<mListLinks.size();++i)
    {
        MMBond& bond = mParent->getBond((const size_t &) mListLinks.at(i));
        if (onlyUsed && !bond.isSelected()) continue;
        oss<< " |-"<<bond<<"\t"<<bond.dist()<<"\n";
    }
    return oss.str();
}


void protspace::MMAtom::delBond(MMBond& bond) throw(ProtExcept)
try
{
    delLink(bond.getMID());
}
catch(ProtExcept &e)
{

    e.addHierarchy("protspace::MMAtom::delBond");
    e.addDescription(bond);
    throw;
}



bool protspace::MMAtom::hasBondWith(const MMAtom& atom)const
{
    const std::vector<int>::const_iterator itPos
            = std::find(mListDots.begin(),
                        mListDots.end(),
                        atom.getMID());

    return (itPos != mListDots.end());
}




protspace::MMBond& protspace::MMAtom::getBond(const size_t&  pos) const throw(ProtExcept)
try{

    if (pos >= mListLinks.size())
        throw_line("310201",
                   "protspace::MMAtom::getBond",
                   "position is above the number of bond for this atom");
    return mParent->getBond((const size_t &) mListLinks.at(pos));
}catch(ProtExcept &e)
{
    assert(e.getId()!="350501");
    throw;
}






protspace::MMBond &protspace::MMAtom::getBond(const MMAtom& atom) const throw(ProtExcept)
{
    // Finding the atom :
    const std::vector<int>::const_iterator itPos
            = std::find(mListDots.begin(),
                        mListDots.end(),
                        atom.getMID());

    if (itPos == mListDots.end())
        throw_line("310301",
                   "protspace::MMAtom::getBond",
                   "No bond found between the two atoms");


    try{
        // Finding the key in the array of listDots for this atom :
        const size_t dist= (size_t) std::distance(mListDots.begin(), itPos);

        assert(dist < mListDots.size());

        // Finding the bond position (in the molecule) based on the key:
        const int& position(mListLinks.at(dist));

        // Getting the bond:
        MMBond& bond =mParent->getBond((const size_t &) position);

        return bond;
    }catch (ProtExcept &e)
    {
        assert(e.getId()!="350501");
        std::ostringstream oss;
        oss<<"Finding bond between : \n"<<
             "|->"<<getIdentifier()+"\n"<<
             "|->"<<atom.getIdentifier()<<"\n";
        e.addDescription(oss.str());
        e.addHierarchy("protspace::MMAtom::getBond");
        throw;
    }

}






const uint16_t& protspace::MMAtom::getBondType(const MMAtom& atom) const throw(ProtExcept)
try{
    const std::vector<int>::const_iterator itPos
            = std::find(mListDots.begin(),
                        mListDots.end(),
                        atom.getMID());


    if (itPos == mListDots.end())
        throw_line("310401",
                   "protspace::MMAtom::getBondType",
                   "No bond found between the two atoms");

    // Finding the key in the array of listDots for this atom :
    const size_t dist((size_t) std::distance(mListDots.begin(), itPos));

    assert(dist < mListDots.size());

    // Finding the bond position (in the molecule) based on the key:
    const int& position(mListLinks.at(dist));

    // Getting the bond:
    const MMBond& bond =mParent->getBond((const size_t &) position);

    return bond.getType();

}catch (ProtExcept &e)
{
    assert(e.getId()!="350501");
    std::ostringstream oss;
    oss<<"Finding bond between : \n"<<
         "|->"<<getIdentifier()+"\n"<<
         "|->"<<atom.getIdentifier()<<"\n";
    e.addDescription(oss.str());
    e.addHierarchy("protspace::MMAtom::getBondType");
    throw;
}


std::string protspace::MMAtom::getIdentifier() const
{
    std::ostringstream oss;
    int len=0;
    if (mParent->numAtoms()<10)      len=1;
    else if (mParent->numAtoms()<100)     len=2;
    else if (mParent->numAtoms()<1000)    len=3;
    else if (mParent->numAtoms()<10000)   len=4;
    else if (mParent->numAtoms()<100000)  len=5;
    else if (mParent->numAtoms()<1000000) len=6;
    const MMResidue& mResidue= mParent->getResidue(mResidueId);
    oss << std::setw(4)<<mName
        <<"("  <<std::setw(len)<<mFId
       <<"/"  <<std::setw(len)<<mMId
      <<")::"<<std::setw(2)<<mResidue.getChainName()
     << "::"<<std::setw(3)<<mResidue.getName()
     << " ("<<std::setw(4)<<mResidue.getFID()
     << "/" <<std::setw(4)<<mResidue.getMID()<<")"
        ;
    return oss.str();

}


protspace::MMAtom& protspace::MMAtom::getAtom(const size_t &pos) const throw(ProtExcept)
{

    if (pos>= mListDots.size())
        throw_line("310501",
                   "protspace::MMAtom::getAtom",
                   "pos is above the number of bonds for this atom") ;
    try{
        return mParent->getAtom((const size_t &) mListDots.at(pos));
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="030401");
        std::ostringstream oss;
        oss<<"Position asked: "<<pos<<"\n"
          <<"ID asked : "<< mListDots.at(pos)<<"\n"
         <<"Number of atoms:"<< mParent->numAtoms()<<"\n";
        e.addDescription(oss.str());
        throw;
    }
}





protspace::MMAtom& protspace::MMAtom::getAtomNotAtom(const MMAtom& atom)const throw(ProtExcept)
{
    try{
        for(size_t i=0;i< mListDots.size();++i)
        {
            if (mListDots.at(i)== atom.getMID())continue;
            return mParent->getAtom((const size_t &) mListDots.at(i));
        }
        throw_line("310601",
                   "protspace::MMAtom::getAtomNotAtom",
                   "No alternative atom found") ;
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="030401");
        std::ostringstream oss;
        oss<<"Atom to avoid : "<<atom.getIdentifier()<<"\n"
          <<"Number of atoms:"<< mParent->numAtoms()<<"\n";
        e.addDescription(oss.str());
        throw;
    }
}


protspace::MMResidue& protspace::MMAtom::getResidue()const throw(ProtExcept)
try{
    if (mResidueId==-2) throw_line("310701",
                                   "protspace::MMAtom::getResidue",
                                   "No residue defined for this atom");
    assert(mParent!=NULL);
    return mParent->getResidue(mResidueId);
}
catch(ProtExcept &e)
{
    assert(e.getId()!="351901");
    throw;
}




void protspace::MMAtom::setResidue(MMResidue* res)
{
    assert(res!=NULL);
    mResidueId=res->getMID();
}














void protspace::MMAtom::setMOL2Type(const std::string& mol2type,
                                    const bool& wAtomicNum) throw(ProtExcept)
{
    if (mol2type=="")
        throw_line("310801",
                   "protspace::MMAtom::setMOL2Type",
                   "No type given");

    if (mol2type== "S.o") mMOL2type="S.O";
    if (mol2type=="Any" )

    {
        const std::string& rname(getResidue().getName());
        if (getResidue().numAtoms()==1){
     if      (rname.find("CL")!=std::string::npos){mMOL2type="Cl";mAtomicNumber=17;return;}
     else if (rname.find("NA")!=std::string::npos){mMOL2type="Na";mAtomicNumber=11;return;}
        }
    }

    for (size_t iMOL2=0; iMOL2 < NB_MOL2;++iMOL2)
    {
        if (mol2type != MOL2_TYPE[iMOL2].mol2)continue;
        mMOL2type=mol2type;
        if(wAtomicNum) mAtomicNumber=MOL2_TYPE[iMOL2].atomic_num;
        return;
    }

static ProtPool& pool=ProtPool::Instance();
    if (ProtExcept::gEnforceRule==STRICT)

        throw_line("310802",
                   "protspace::MMAtom::setMOL2Type",
                   "Unrecognized MOL2 Type : "+mol2type);
    LOG_WARN("Unrecognized MOL2 Type : "+mol2type+" FOR "+getIdentifier());
    size_t mObjPos, mDPos;
    std::string& lower=pool.string.acquireObject(mObjPos);
    lower=mol2type;toLowercase(lower);
    std::string& d=pool.string.acquireObject(mDPos);
    for(size_t iMOL2=0;iMOL2 < NB_MOL2;++iMOL2)
    {
        d=MOL2_TYPE[iMOL2].mol2;toLowercase(d);
        if (d!= lower)continue;
        if (ProtExcept::gEnforceRule==SHOW)
            std::cerr <<"Wrong definition for MOL2 Type : "+mol2type<<" should be "<< MOL2_TYPE[iMOL2].mol2<<std::endl;
        mMOL2type=MOL2_TYPE[iMOL2].mol2;
        if(wAtomicNum) mAtomicNumber=MOL2_TYPE[iMOL2].atomic_num;
        return;
    }
    LOG_ERR("Unrecognized MOL2 Type : "+mol2type+" for atom "+getIdentifier());
    if (ProtExcept::gEnforceRule==SHOW) std::cerr <<"Unrecognized MOL2 Type : "+mol2type<<std::endl;
    setMOL2Type("Du",wAtomicNum);
    pool.string.releaseObject(mObjPos);
    pool.string.releaseObject(mDPos);

}
