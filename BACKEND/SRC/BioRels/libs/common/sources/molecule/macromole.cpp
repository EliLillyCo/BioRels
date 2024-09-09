#include <sstream>
#include <fstream>
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/string_convert.h"
#include "headers/parser/ofstream_utils.h"
#undef NDEBUG /// Active assertion in release


const uint16_t& protspace::MacroMole::getMoleType() const
{
    return mMoleType;
}
protspace::MacroMole::MacroMole(const std::string &name,
                                const bool& owner):
    Group<MMAtom,MMBond,MacroMole>(this,owner),
    ids(0,0),
    mName(name),
    mCreateAtom(0,this),
    mTempChain(this,"X"),
    mTempResidue(this,-1,"TEMP",-1),
    mIsResidueNumberOk(false),
    mMoleType(MOLETYPE::UNDEFINED)
{
    mListResidues.reserve(100);
    mTempResidue.setMID(-1);
}

protspace::MacroMole::~MacroMole()
{
    for(size_t i=0;i<mListResidues.size();++i)delete mListResidues[i];
    for(size_t i=0;i<mListChain.size();++i)delete mListChain[i];
    for(size_t i=0;i<mListRing.size();++i)delete mListRing[i];
}


protspace::MMAtom& protspace::MacroMole::addAtom(const int& pResId)throw(ProtExcept)
{
    if (!mOwner)
        throw_line("350101",
                   "MacroMole::addAtom",
                   "This molecule is an alias. Cannot create atom on an alias");

    try
    {
        MMAtom* atom = new MMAtom(mCreateAtom);
        mListDot.push_back(atom);
        atom->mResidueId=pResId;
        if (atom->mResidueId==-1)mTempResidue.addAtom(atom);
        else mListResidues.at(pResId)->addAtom(atom);

        return *atom;
    }catch (ProtExcept &e)  {
        /// No atom given -> Shouldn't happen
        assert(e.getId()!="320101");
        throw;
    }  catch (std::bad_alloc &e)  {
        throw_line("350102","MacroMole::addAtom",
                   "Bad allocation append\n"+std::string(e.what()));
    }
    catch(std::out_of_range &e)
    {
        assert(1==0);
    }

}







protspace::MMAtom& protspace::MacroMole::addAtom(MMResidue& residue)throw(ProtExcept)
try
{
    if (residue.mParent!= this)
        throw_line("350201","MacroMole::addAtom(RES)",
                   "Given residue is not in this molecule ");
    return addAtom(residue.getMID());
}catch (ProtExcept &e)
{
    if (e.getId()!="350201")e.addHierarchy("MacroMole::addAtom(RES)");
    e.addDescription("Residue involved "+residue.getIdentifier());
    throw;
}







protspace::MMAtom& protspace::MacroMole::addAtom(MMResidue &residue,
                                                 const Coords& coord,
                                                 const std::string& atomName,
                                                 const std::string& MOL2Name,
                                                 const std::string& mElement)
{
    if (residue.mParent!= this)
        throw_line("350301","MacroMole::addAtom(RES,COORDS)",
                   "Given residue is not in this molecule ");

    try{
        MMAtom& atom=addAtom(residue.getMID());
        atom.pos()=coord;
        atom.setName(atomName);
        if(MOL2Name!="")
        {
            atom.setMOL2Type(MOL2Name);
            if (mElement!="" && atom.getElement()!=mElement)
                throw_line("350302",
                           "MacroMole::addAtom(RES,COORDS)",
                           "Associated element to MOL2 :"+atom.getElement()+" "
                           +"is different than given element: "+mElement);
        }
        else if (mElement!="")atom.setAtomicType(mElement);
        return atom;
    }catch(ProtExcept &e)
    {
        ///if setAtomicType is called, mElement shouldn't be empty
        assert(e.getId()!="310801");
        if (e.getId()!="350302") e.addHierarchy("protspace::MacroMole::addAtom(RES,COORDS)");
        e.addDescription("Residue : "+residue.getIdentifier());
        e.addDescription("Atom Name :"+atomName);
        e.addDescription("MOL2 Name :"+MOL2Name);
        e.addDescription("Element :"+mElement);
        throw;
    }
}





protspace::MMAtom& protspace::MacroMole::getAtom(const size_t& pos)  throw(ProtExcept)
try
{
    return getDot(pos);
}catch(ProtExcept &e)
{
    e.addHierarchy("protspace::MacroMole::getAtom");
    throw;
}


const protspace::MMAtom& protspace::MacroMole::getAtom(const size_t& pos) const throw(ProtExcept)
try
{
    return getDot(pos);
}catch(ProtExcept &e)
{
    e.addHierarchy("protspace::MacroMole::getAtom");
    throw;
}




protspace::MMAtom&
protspace::MacroMole::getAtomByFID(const int& FID) const throw(ProtExcept)
{
    try{
    for (size_t i = 0, nAtoms = numAtoms(); i < nAtoms; ++i) {
        MMAtom& atom = getDot(i);
        if (atom.getFID() == FID) {
            return atom;
        }
    }}catch(ProtExcept &e){assert(e.getId()!="030401");throw;}
    throw_line("350401","MacroMole::getAtomByFID",
               "Given ID not found "+std::to_string(FID));
}






protspace::MMBond& protspace::MacroMole::getBond(const size_t &pos) const throw(ProtExcept)
{

    if(pos >= mListLinks.size())
        throw_line("350501",
                   "MacroMole::getBond",
                   "Given position is above the number of bonds");
    return *mListLinks.at(pos);

}


void protspace::MacroMole::serialize(std::ofstream &out) const
{
    // READING ID
    out.write((char*)&mMId,sizeof(mMId));
    out.write((char*)&mFId,sizeof(mFId));
    // WRITING NAME OF GRAPH
    size_t length=mName.size();
        out.write((char*)&length,sizeof(size_t));
        out.write(mName.c_str(),mName.size());



    // LISTING VERTEX:
    length=mListDot.size();
    out.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<length;++i)
        mListDot.at(i)->serialize(out);


    length=mListLinks.size();
    out.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<length;++i)
    {
        const MMBond& bd=getLink(i);
        bd.serialize(out);
    }



    length=mListResidues.size();

    out.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<length;++i)
    {

        mListResidues.at(i)->serialize(out);
    }

    length=mListChain.size();
    out.write((char*)&length,sizeof(size_t));

    for(size_t i=0;i<length;++i)
    {

        mListChain.at(i)->serialize(out);
    }

}


void protspace::MacroMole::unserialize(std::ifstream& ifs)
{
    // READING ID of molecule
    ifs.read((char*)&mMId,sizeof(int));
    ifs.read((char*)&mFId,sizeof(int));

    // READING NAME OF GRAPH:
    size_t length=0;

    readSerializedString(ifs,mName);
    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    mListDot.reserve(length);
    for(size_t i=0;i<length;++i)
    {
        MMAtom& atom=addAtom();
        atom.unserialize(ifs);
    }


    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    int mid,fid,dot1,dot2;bool sel;
    uint16_t type=0x0000;
    mListLinks.reserve(length);
    for(size_t i=0;i<length;++i)
    {
        ifs.read((char*)&type,sizeof(uint16_t));
        ifs.read((char*)&mid,sizeof(int));
        ifs.read((char*)&fid,sizeof(int));
        ifs.read((char*)&dot1,sizeof(int));
        ifs.read((char*)&dot2,sizeof(int));
        ifs.read((char*)&sel,sizeof(bool));
        MMBond& bd=addBond(*mListDot.at((unsigned int) dot1),
                           *mListDot.at((unsigned int) dot2), type, fid);
        bd.setMID(mid);
        bd.setUse(sel);
}

    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    mListResidues.reserve(length);
    for(size_t i=0;i<length;++i)
    {
        MMResidue* res = new MMResidue(this);
        res->unserialize(ifs);
        mListResidues.push_back(res);
    }

    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    mListChain.reserve(length);
    for(size_t i=0;i<length;++i)
    {
        MMChain* chain = new MMChain(this,"X");
        chain->unserialize(ifs);
        mListChain.push_back(chain);

    }
}







void protspace::MacroMole::clearBond()
{
    for(size_t i=0;i<mListDot.size();++i)
    {
        MMAtom& atom=*mListDot.at(i);
        atom.mListDots.clear();
        atom.mListLinks.clear();
    }
    clearLinks();

}







protspace::MMBond& protspace::MacroMole::addBond(MMAtom& atom1,
                                                 MMAtom& atom2,
                                                 const uint16_t &bondType,
                                                 const int& fid) throw(ProtExcept)
{
    if (!mOwner)
        throw_line("350601",
                   "MacroMole::addBond",
                   "This molecule is an alias. Cannot create residue on an alias");
    if (&atom1.getMolecule()!= this)
        throw_line("350602",
                   "MacroMole::addBond",
                   "Atom not part of this molecule");
    if (&atom2.getMolecule()!= this)
        throw_line("350603",
                   "MacroMole::addBond",
                   "Atom not part of this molecule");
    if (&atom1== &atom2)
        throw_line("350604",
                   "MacroMole::addBond",
                   "Both atoms are the same");


    try
    {
        MMBond& bond=createLink(atom1,atom2,bondType);
        bond.setFID(fid);
        return bond;
    }catch (ProtExcept &e)
    {
        assert(e.getId()!="030301" &&e.getId()!="030302" && e.getId()!="030204");
        /// This should only be bad allocation
        e.addHierarchy("protspace::MacroMole::addBond");
        e.addDescription(atom1.toString()+" "+atom2.toString());
        throw;
    }
}

void protspace::MacroMole::delBond(MMBond& bond) throw(ProtExcept)
{

    // Check that this bond is indeed in the molecule

    if (mOwner && &bond.mParent != this)
        throw_line("350701",
                   "MacroMole::delBond",
                   "Given Bond is not part of this molecule");
    // Find the position of the bond in the list of bond of the molecule
    std::vector<MMBond*>::iterator itPos= std::find(mListLinks.begin(),
                                                    mListLinks.end(),
                                                    &bond);

    if (itPos == mListLinks.end())
        throw_line("350702",
                   "MacroMole::delBond",
                   "Given Bond is not part of this molecule");

    //Each bond has an associated MID. The latter defines the position
    // of the bond in the list of bond in the molecule (1, 2, 3, 4, 5)
    // When we delete a bond (let's say bond 3), the MID of bond 4 and 5
    // becomes wrong since their position are 3 and 4. So we need to update
    // all bonds above the bond that we delete.
    bool bond_del=false;
    const int bond_mid = bond.mMId;
    MMAtom& atom1=bond.mDot1;
    MMAtom& atom2=bond.mDot2;


    try{

        if (mOwner)// Delete the link from the atom
        {
            atom1.delBond(bond);
            atom2.delBond(bond);
        }

        // Delete the bond in the bond list. This does not delete the bond itself
        mListLinks.erase(itPos);

        if (!mOwner)return;

        // Delete the bond
        delete &bond;
        bond_del=true;

        renumBonds(bond_mid);
    }catch (ProtExcept &e)
    {
        assert(e.getId()!="020101");
        e.addHierarchy("MacroMole::delBond");
        if (bond_del) e.addDescription("Deletion of bond complete\n");
        else e.addDescription(std::string("Deleting bond :")+bond+"\n");
        e.addDescription("MMAtom 1 involved :\n"+atom1.toString()+"\n"
                          "MMAtom 2 involved :\n"+atom2.toString()+"\n");
        throw;
    }

}
void protspace::MacroMole::renumBonds(const size_t& starter)
try{
    // Scanning all bond with MID above the deleted one
    /// To update their MID
    for (size_t iBond= (size_t) starter; iBond < mListLinks.size(); ++iBond)
    {
        MMBond& bond_loop= *mListLinks.at(iBond);
        //Since atom does not have reference nor pointer to the bond
        // but the associated MID, we need to update them as well
        // by giving the former MID and the new MID
        bond_loop.getAtom1().updateLink(bond_loop.mMId,iBond);
        bond_loop.getAtom2().updateLink(bond_loop.mMId,iBond);
        bond_loop.mMId=iBond;
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="020201");
    e.addHierarchy("MacroMole::renumBonds");
    throw;
}




bool protspace::MacroMole::hasBond(const MMBond& bond)const
{
    return mListLinks.end() != std::find(mListLinks.begin(),
                                         mListLinks.end(),
                                         &bond);
}






void protspace::MacroMole::delAtom(MMAtom& atom, const bool& noring) throw(ProtExcept)
{
    if (!mOwner)
    {
        std::vector<MMAtom*>::iterator it =
                std::find(mListDot.begin(),mListDot.end(),&atom);
        if (it == mListDot.end())
            throw_line("350801",
                       "MacroMole::delAtom",
                       "Atom not found in molecule");
        mListDot.erase(mListDot.begin()+std::distance(mListDot.begin(),it));
        return;
    }

    try{

        getResidue(atom.mResidueId).delAtom(atom);


    // Checking whether a ring has this atom:
    if (!noring)
        for (size_t iRing=0;iRing < mListRing.size();++iRing)
        {
            MMRing& ring = *mListRing.at(iRing);
            if (!ring.isInRing(atom))continue;
            delRing(ring);
            iRing=0;
        }



        delDot(atom);
    }catch (ProtExcept &e)
    {
       /// The residue must exists and the atom to deleted must be in the residue
        assert(e.getId()!="320301" && e.getId()!="320302"
            && e.getId()!="070801" && e.getId()!="352601");

        e.addHierarchy("MacroMole::delAtom");;
        throw;
    }

}

void protspace::MacroMole::select(const bool& use)
{
    if (mOwner)
    {
        for (size_t iAtm=0;iAtm < mListChain.size();++iAtm)
        {
            mListChain.at(iAtm)->select(use,false);
        }
        for (size_t iAtm=0;iAtm < mListResidues.size();++iAtm)
        {
            mListResidues.at(iAtm)->select(use,false,false);
        }
    }
    for (size_t iAtm=0;iAtm < mListDot.size();++iAtm)
    {
        mListDot.at(iAtm)->select(use,false);
    }
    for (size_t iAtm=0;iAtm < mListLinks.size();++iAtm)
    {
        mListLinks.at(iAtm)->setUse(use,false);
    }
    if (!mOwner)
    {
        for (size_t iAtm=0;iAtm < mListChain.size();++iAtm)
        {
            mListChain.at(iAtm)->checkSelection();
        }
        for (size_t iAtm=0;iAtm < mListResidues.size();++iAtm)
        {
            mListResidues.at(iAtm)->checkUse();
        }
    }
}

std::string protspace::MacroMole::toString(const bool& onlyUsed)const
{
    std::ostringstream oss;
    try
    {

        for (size_t iAtm=0 ;iAtm < mListDot.size()  ; ++iAtm)
            if (!onlyUsed || (onlyUsed && mListDot[iAtm]->isSelected()))
                oss<< mListDot[iAtm]->toString(onlyUsed,true);

        for (size_t iBd =0 ;iBd  < mListLinks.size()  ; ++iBd )
            if (!onlyUsed || (onlyUsed && mListLinks[iBd]->isSelected()))
                oss<< mListLinks[iBd]->toString()<<"\n";
        for (size_t iBd =0 ;iBd  < mListResidues.size()  ; ++iBd )
            if (!onlyUsed || (onlyUsed && mListResidues[iBd]->isSelected()))
                oss<< mListResidues[iBd]->toString()<<"\n";

        for(size_t iRing=0;iRing < mListRing.size();++iRing)
        {
            oss << mListRing[iRing]->toString()<<"\n";
        }
    }catch(ProtExcept &e)
    {
        std::cerr << "In protspace::MacroMole::toString"<<std::endl;
        std::cerr <<e.toString()<<std::endl;
    }
    return oss.str();
}

protspace::MMChain& protspace::MacroMole::getChain(const signed char& pos)  throw(ProtExcept)
{
    if (pos==-1)return mTempChain;
    if (static_cast<size_t>(pos) >= mListChain.size())
        throw_line("350901",
                   "MacroMole::GetChain",
                   "Position above the number of chain");
    return *mListChain.at((unsigned int) pos);
}

const protspace::MMChain& protspace::MacroMole::getChain(const signed char& pos )  const throw(ProtExcept)
{
    if (pos==-1)return mTempChain;
    if (static_cast<size_t>(pos) >= mListChain.size())
        throw_line("351001",
                   "MacroMole::getChain",
                   "Position above the number of chain");
    return *mListChain.at((unsigned int) pos);
}

protspace::MMChain& protspace::MacroMole::getChain(const std::string& name)const throw(ProtExcept)
{
    for (size_t i=0;i<mListChain.size();++i)
    {
        MMChain& chain = *mListChain.at(i);
        if ( chain.getName() == name) return chain;
    }
    throw_line("351101",
               "protspace::MacroMole::getChain",
               "Chain with name "+name+" not found");

}



void protspace::MacroMole::clear() {
    for(size_t i=0;i<mListResidues.size();++i)delete mListResidues[i];
    for(size_t i=0;i<mListChain.size();++i)delete mListChain[i];
    for(size_t i=0;i<mListRing.size();++i)delete mListRing[i];

    mName="";mListResidues.clear();mListChain.clear();
    mListErrorAtom.clear();
    mListErrorBond.clear();
    mListErrorResidue.clear();
    mTempResidue.clearAtoms();
    mListRing.clear();

    mIsResidueNumberOk=true;

    mMoleType=MOLETYPE::UNDEFINED;
    Group::clear();
}
