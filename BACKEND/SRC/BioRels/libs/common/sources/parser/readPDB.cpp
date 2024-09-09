#include "headers/parser/readPDB.h"
#include "headers/statics/strutils.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/protpool.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release

protspace::ReadPDB::ReadPDB(const std::string& path):
    ReaderBase(path),
    mCurrResidue((MMResidue*)NULL),
    mIsNMR(false),
    mIsAbove100K(false),
    nInsert(0),

    mNumBond(0),
    mNError(0),
    mCurrInsert(" "),
    mCurrNMRModel(-1)
{}

bool protspace::ReadPDB::findPos(const std::vector<int>& list,
                                 const int& pos, unsigned int& arrPos)const
{

    if (list.empty())return false;
    const auto it = std::find(list.begin(),list.end(),pos);
    if(it == list.end())return false;
    arrPos = std::distance(list.begin(),it);
    return true;

}

protspace::MMAtom& protspace::ReadPDB::findAtom(const int& pos, bool& isFound) const
{
        unsigned int arrPos=0;isFound=true;
        if (findPos(mPosID,pos,arrPos))
        {
            return *posATI.at(arrPos);
        }
        isFound=false;
        if (!mIgnoredAtom.empty() &&findPos(mIgnoredAtom,pos,arrPos))
            {
            return *posATI.at(0);}
        throw_line("420101",
                          "ReadPDB::findAtom",
                          "Atom serial number not found: "+std::to_string(pos)+"\n"+mLigne);

}

void protspace::ReadPDB::handleAtomLine(protspace::MacroMole& molecule)throw(ProtExcept)
{

    if (mIsNMR && mCurrNMRModel!=1 && mCurrNMRModel!=-1)return;

    mStrNewCurrRes=mLigne.substr(17,9);
    try{
        if (mStrNewCurrRes == mStrCurrRes)
        {
            if (mLigne.at(16)!=' ')
            {
                mListAltLines.push_back(mLigne);
                mPrevAltPos=mLigne.at(16);
            }
            else if (!mListAltLines.empty() &&
                     mLigne.substr(76,2).find("H")!=std::string::npos)
            {

                mLigne.replace(16,1,mPrevAltPos);
                mListAltLines.push_back(mLigne);

            }
            else loadAtom(molecule);
        }
        else
        {
            if (!mListAltLines.empty())
            {
                const std::string tmpL(mLigne);
                processAlternativePositions(mListAltLines,molecule);
                mListAltLines.clear();
                mLigne=tmpL;
            }
            if (mLigne.at(16)!=' ' )  mListAltLines.push_back(mLigne);
            else  loadAtom(molecule);

            mStrCurrRes=mStrNewCurrRes;
        }
    }catch(ProtExcept &e)
    {
        //                    ProtExcept::verboseLevel=2;
        LOG_ERR(e.getId()+"|"+e.getLocation()+"|"+e.getDescription());
        //                    ProtExcept::verboseLevel=3;
        mNError++;
        if (mNError==50)throw_line("502010",
                                   "ReadPDB::handleAtomLine",
                                   "Too many Errors to continue reading");
    }
}


///TODO Handle alt pos and when ignoring them, add warning that we ignored them
void protspace::ReadPDB::load(MacroMole &molecule) throw(ProtExcept)
{

    try{

        if (!mIfs.is_open())
            open();
        std::vector<std::string> toks(10,"");
        std::string header="";
        mCurrResidue=(MMResidue*)NULL;
        /// Since alternative positions for a given atom can be defined across the whole residue
        /// definition, we use column 17 to 26 to uniquely define a residue identifier
        /// Every alternative position, no matter the order, will be put in the temporary listAtlLine
        /// When we change the residue identifier, we process the listAtlLine


        mListAltLines.clear();
        // Patch For 209l (A::ALA (  73/  72)), 205l ( A::ALA (  44/  38)), 104l (N( 351/ 350):: A::ALA (  44/  44))

        while(getLine())
        {

            header= mLigne.substr(0,6);


            if (header=="HETNAM")
            {
                for(std::string& str:toks)str="";

                if (tokenReuseStr(mLigne.substr(7),toks," ")==2)
                    for(size_t i=0;i< toks.size();++i)
                    {
                        if (toks.at(1).find_first_not_of("0123456789")!=std::string::npos)continue;
                        mMapNames.insert(std::make_pair(toks.at(0),toks.at(1)));
                    }

            }
            else if (header=="ATOM  " || header == "HETATM")
            {
                handleAtomLine(molecule);
            }
            else if(header == "CONECT")
            {
                if (!mListAltLines.empty()){
                    const std::string tmpL(mLigne);
                    processAlternativePositions(mListAltLines,molecule);mListAltLines.clear();
                    mLigne=tmpL;
                }
                if (!mIsAbove100K) loadBond(molecule);
            }// END CONECT
            else if (header == "EXPDTA")
            {
                if (mLigne.find("NMR")!= std::string::npos) mIsNMR=true;
            }
            else if (header=="MODEL ")
            {
                mCurrNMRModel=atoi(mLigne.substr(10,4).c_str());
            }
            else if (header=="NUMMDL") {mIsNMR=true;}
            else if (header=="ENDMDL"){mCurrNMRModel=0;}
            else if (header.substr(0,3)=="END")break;

        }
        if (!mListAltLines.empty()){
            processAlternativePositions(mListAltLines,molecule);mListAltLines.clear();
        }
        checkInsertedResidues(molecule);
    }catch(ProtExcept &e)
    {
          if (e.getId()=="030303"||e.getId()=="351401"||e.getId()=="351502"||e.getId()=="350102")
                throw_line("420301","ReadPDB::load","Unable to read file - Memory allocation issue");
        e.addHierarchy("MoleRead::loadAsPDB");throw;
    }

}

void protspace::ReadPDB::loadBond(MacroMole& molecule)
{

    bool isFound,  isGoingDown=false;
    int prevPos=-1;
    try{
        const int posR=atoi(mLigne.substr(6,5).c_str());
        if (posR < prevPos)isGoingDown=true;
        prevPos=posR;

        MMAtom& RefMMAtom=findAtom((isGoingDown&&mIsAbove100K)?posR+100000: posR,isFound);

        if (!isFound)return;

        const size_t length=mLigne.find_last_not_of(" ");
        for (size_t iLen=16; iLen<=31;iLen+=5)
        {

            // +3 matters in the case of atom ids lower than 10K
            if (length+3 <iLen) break;

            try{
                const int posC=atoi(mLigne.substr(iLen-5,5).c_str())
                        +((isGoingDown&&mIsAbove100K)?100000:0);

                if (posC==posR)
                {
                    ///TODO add FILE FORMAT ERROR
                    continue;
                }

                MMAtom& Comp=findAtom(posC,isFound);

                if (!isFound)continue;
                if (RefMMAtom.hasBondWith(Comp))
                {
                    ErrorBond eb(RefMMAtom,Comp,ERROR_BOND::BOND_EXISTS,"Atom are already linked");
                    molecule.addNewError(eb);
                    mNError++;

                }
                else if ((Comp.getResName()=="HOH"||RefMMAtom.getResName()=="HOH")
                       && RefMMAtom.getResidue().getFID()!= Comp.getResidue().getFID())
                {
                    LOG_WARN("A water molecule cannot be bonded to another residue "+mLigne);
                    continue;
                }
                else
                {
                    molecule.addBond(RefMMAtom, Comp, 1, (const int &) mNumBond);mNumBond++;
                }
            }catch(ProtExcept &e)
            {
                /// ALias molecule
                assert(e.getId()!="350601");
                /// Atom not part of the molecule ????
                assert(e.getId()!="350602" && e.getId()!="350603");

                if (e.getId()!="420101")
                switch (iLen) {
                case 16: e.addDescription("Second atom not found"); break;
                case 21: e.addDescription("Third atom not found"); break;
                case 26: e.addDescription("Fourth atom not found"); break;
                case 31: e.addDescription("Fifth atom not found"); break;
                default: e.addDescription("Unknown atom not found");break;
                }
                else e.addHierarchy("ReadPDB::loadBond");
                e.addDescription("Line involved: "+mLigne);
                throw;
            }
        }
    }catch(ProtExcept &e)
    {
        throw;
    }

}


void protspace::ReadPDB::assignFormalCharge(MMAtom& atom)const
{
    static const std::map<std::string,signed char> rules=
    {{"1-",-1},{"-1",-1},{"2-",-2},{"-2",-2},{"3-",-3},
     {"1+",+1},{"1+",+1},{"1 ",+1},{" 1",+1},
     {"2+",+2},{"2+",+2},{"2 ",+2},{" 2",+2},
     {"3+",+3},{"3+",+3},{"3 ",+3},{" 3",+3},{"  ",0}};
    size_t poolPos;
    std::string& charge(protspace::ProtPool::Instance().string.acquireObject(poolPos));
    charge=mLigne.substr(78,2).c_str();
    const auto it = rules.find(charge);
    if (it == rules.end()){
        ErrorAtom eb(atom, ERROR_ATOM::BAD_CHARGE, "unrecognized charge " + charge);
        atom.getMolecule().addNewError(eb);
    }
    atom.setFormalCharge((*it).second);
    protspace::ProtPool::Instance().string.releaseObject(poolPos);

}

protspace::ReadPDB::~ReadPDB()
{

}


bool protspace::ReadPDB::createResidue(MacroMole& molecule,
                                       const std::string& resName)
{
    bool check=false;
    StringPoolObj chain,insertCode;

    try{
        chain.get()      =  removeSpaces(mLigne.substr(21,1));
        const int         resFID     =  atoi(mLigne.substr(22,4).c_str());

        if (!checkChainRule(chain.get()))return false;

        insertCode.get() = mLigne.substr(26,1);
        const bool hasInsert(insertCode.get() != " ");


        if (mCurrResidue == (MMResidue*)NULL){
            if (chain.get().empty())
            {
                chain.get()="X";
                LOG_ERR(mLigne+" Not compliant - Chain missing. Assigning to chain "+chain.get());
            }
            mCurrResidue=& molecule.addResidue(resName,
                                               chain.get(),
                                               resFID+((hasInsert)?1000+nInsert:0),
                                               mForceResCheck);
            if (hasInsert)listInsertedRes.push_back(mCurrResidue);
            mCurrInsert=insertCode.get();
            return true;
        }
        if (chain.get().empty())
        {
            chain.get()=mCurrResidue->getChainName();
            LOG_ERR(mLigne+" Not compliant - Chain missing. Assigning to chain "+chain.get());
        }
        const bool isSameChain(mCurrResidue->getChainName()==chain.get());
        const bool isSameName(mCurrResidue->getName()==resName);
        const bool isSameInsert(insertCode.get()==mCurrInsert);
        const bool isSameFID(mCurrResidue->getFID()==(resFID+
                                                      ((hasInsert&& isSameInsert) ?nInsert+1000:0)));



        if (isSameChain && isSameFID && isSameName)
        {
            ///Same definition with no insert or same insert => Remains
            if (!hasInsert || isSameInsert)
            {

                mCurrInsert=insertCode.get();
                check=true;
            }
            else // Different insert
            {
                ///
                mCurrInsert=insertCode.get();
                ++nInsert;
                mCurrResidue=&molecule.addResidue(resName,chain.get(),resFID+1000+nInsert,mForceResCheck);
                listInsertedRes.push_back(mCurrResidue);
                ErrorResidue err("Original Residue ID:"+std::to_string(resFID)+insertCode.get()+"\nDue to Insertion Code",
                                 *mCurrResidue);
                molecule.addNewError(err);
                check=true;
            }
        }
        else
        {
            if (!hasInsert)
            {

                mCurrResidue=&molecule.addResidue(resName,chain.get(),resFID,mForceResCheck);
                mCurrInsert=insertCode.get();
                check=true;
            }
            else
            {

                mCurrInsert=insertCode.get();
                ++nInsert;
                mCurrResidue=&molecule.addResidue(resName,chain.get(),resFID+1000+nInsert,mForceResCheck);
                listInsertedRes.push_back(mCurrResidue);
                ErrorResidue err("Original Residue ID:"+std::to_string(resFID)+insertCode.get()+"\nDue to Insertion Code",
                                 *mCurrResidue);
                molecule.addNewError(err);
                check=true;
            }
        }
    }catch(ProtExcept &e)
    {
        /// Molecule cannot be an alias
        assert(e.getId()!="351501");
        e.addHierarchy("ReadPDB::createResidue");
        throw;

    }
    return check;
}





void protspace::ReadPDB::loadAtom(MacroMole &molecule)
{
     size_t poolCoordPos;
    protspace::StringPoolObj atomElement(""), resName(""),atomName("");
    protspace::Coords& coo=protspace::ProtPool::Instance().coord.acquireObject(poolCoordPos);
    try{
        resName = removeSpaces(mLigne.substr(17,3));
        const int FID=  ((mIsAbove100K) ? (const int)molecule.numAtoms() :
                                          atoi(mLigne.substr(6, 5).c_str()));

        if (FID == 99999)mIsAbove100K=true;

        if (!createResidue(molecule,resName.get()))
        {
            mIgnoredAtom.push_back(FID);
            return;
        }

        if (mLigne.length()<80)
        {
            for(size_t i=mLigne.length();i<=80;++i)mLigne+=' ';
        }
        atomName=removeSpaces(mLigne.substr(12,4));

        atomElement=mLigne.substr(76,2);

        if (atomElement.get()=="  ")
        {
            if (resName.get() == "HOH" && atomName.get().substr(0,1)=="O")
            {
                atomElement="O";
            }

            else if (ProtExcept::gEnforceRule==STRICT)
            {
                throw_line("420201",
                           "ReadPDB::loadAtom",
                           "Atom Element not specified");
            }
            else if (ProtExcept::gEnforceRule==LOOSE)
            {
                atomElement="Du";
            }
            else if (ProtExcept::gEnforceRule==SHOW)
            {
                ELOG_ERR("Missing element "+mLigne);

                atomElement="Du";
            }
        }
        coo.setxyz(atof(mLigne.substr(30,8).c_str()),
                   atof(mLigne.substr(38,8).c_str()),
                   atof(mLigne.substr(46,8).c_str()));
        MMAtom& atom = molecule.addAtom(*mCurrResidue,
                                        coo,
                                        atomName.get(),
                                        "",
                                        atomElement.get());


        atom.setBFactor(atof(mLigne.substr(60,6).c_str()));
        mPosID.push_back(FID);
        posATI.push_back(&atom);
        atom.setFID(FID);

        assignFormalCharge(atom);

    }catch(ProtExcept &e)
    {

        /// MOL2 exception cannot exists in a PDB file
        assert(e.getId()!="350302" && e.getId()!="310802");
        /// Molecule cannot be an alias
        assert(e.getId()!="350102");
        /// Residue not found ? Cannot happen
        assert(e.getId()!="350301");

        e.addHierarchy("ReadPDB::loadAtom");
        e.addDescription("Line involved : "+mLigne);
        e.addDescription("Atomic Element : "+atomElement.get());
        protspace::ProtPool::Instance().coord.releaseObject(poolCoordPos);
        throw;
    }
    protspace::ProtPool::Instance().coord.releaseObject(poolCoordPos);
}

void protspace::ReadPDB::processAlternativePositions(const std::vector<std::string> &listLines,
                                                     MacroMole& mole)
{
    //    Logger& log=Logger::Instance();
    const size_t nLine=listLines.size();
    std::map<char, std::vector<size_t>> list;

    char altPos='\0',altPos2='\0';
    if (nLine==1)
    {
        mLigne = listLines.at(0);
        loadAtom(mole);return;
    }
    if (nLine ==2)
    {
        const double occup1= atof(listLines.at(0).substr(54,6).c_str());
        const double occup2= atof(listLines.at(1).substr(54,6).c_str());
        if (occup1 >= occup2){
            mLigne = listLines.at(0);
            mIgnoredAtom.push_back(atoi(listLines.at(1).substr(6,5).c_str()));
        }
        else {
            mLigne=listLines.at(1);
            mIgnoredAtom.push_back(atoi(listLines.at(0).substr(6,5).c_str()));
        }

        loadAtom(mole);
        return;
    }


    std::vector<size_t> tmp2,tmp;
    tmp.push_back(1);
    tmp2.push_back(1);;

    for (size_t iLine=0; iLine < nLine;++iLine)
    {

        const std::string& line = listLines.at(iLine);
        altPos= line.at(16);

        if (altPos == ' ' )continue;

        list.clear();
        tmp.at(0)=iLine;
        list[altPos]=tmp;
        do
        {
            iLine++;
            if (iLine > listLines.size()-1)break;

            const std::string& line2 = listLines.at(iLine);
            const std::string& Header2= line2.substr(0,6);
            if (Header2 != "ATOM  "
              &&Header2 != "HETATM")continue;
            altPos2= line2.at(16);
            if (altPos2 == ' ' )break;
            list[altPos2].push_back(iLine);

        }while(altPos2 != ' ');
        char  maxAltOccup='\0';
        double maxAltOccupValue=0,sumAvg=0;

        for(auto i = list.begin(); i!= list.end();++i)
        {

            double Occupancy=0,count=0;
            const std::vector<size_t>& group = (*i).second;
            assert(!group.empty());
            for (size_t j=0; j< group.size();++j)
            {

                const std::string& ligne=listLines.at(group.at(j));
                protspace::StringPoolObj h_test(ligne.substr(76,2));
                protspace::removeAllSpace(h_test.get());
                if (h_test.get()=="H")continue;
                Occupancy+=atof(ligne.substr(54,6).c_str());

                count++;
            }
            Occupancy/=count;
            sumAvg+=Occupancy;
            if (Occupancy > maxAltOccupValue)
            {
                maxAltOccupValue=Occupancy;
                maxAltOccup=(*i).first;
            }

        }


        for(auto i = list.begin(); i!= list.end();++i)
        {
            const std::vector<size_t>& group = (*i).second;
            if ((*i).first != maxAltOccup)
            {
                for (size_t j=0; j< group.size();++j)
                {

                    mIgnoredAtom.push_back(atoi(listLines.at(group.at(j)).substr(6,5).c_str()));
                }
            }
            else{

                for (size_t j=0; j< group.size();++j)
                {
                    mLigne=listLines.at(group.at(j));
                    loadAtom(mole);
                }
            }
        }
        //        cout << "BEST AVG :" << maxAltOccup << " "<< maxAltOccupValue<<endl;
        //         cout << "SUI AVG : "<< sumAvg<<endl;
        iLine--;
    }

}


void protspace::ReadPDB::clean()
{
    mListChainsRule.clear();
    mIgnoredAtom.clear();
    mListExclusionResidue.clear();
    mCurrResidue=nullptr;
    mIsNMR=false;
    mIsAbove100K=false;
    nInsert=0;
    mNumBond=0;
    mNError=0;
    mCurrInsert=" ";
    mMapNames.clear();
    mPosID.clear();
    posATI.clear();
    mListAltLines.clear();
    mStrCurrRes="";
    mStrNewCurrRes="";
    mPrevAltPos="";
    mCurrNMRModel=-1;
    listInsertedRes.clear();
}

void protspace::ReadPDB::checkInsertedResidues(MacroMole& mole)
{
    bool hasConflict=false;

    for(protspace::MMResidue* resI:listInsertedRes)
    {
        hasConflict=false;
        for(size_t iR=0;iR< mole.numResidue();++iR)
        {
            protspace::MMResidue& pR=mole.getResidue(iR);
            if (pR.getFID()==resI->getFID() && &pR!=resI)
            {
                hasConflict=true;
                break;
            }
        }
        if (hasConflict)break;
    }
    if (!hasConflict)return;
    int maxFID=0;
    for(size_t iR=0;iR< mole.numResidue();++iR)
    {
        protspace::MMResidue& pR=mole.getResidue(iR);
        if (std::find(listInsertedRes.begin(),listInsertedRes.end(),&pR)
                !=listInsertedRes.end())continue;
        if (pR.getFID()>maxFID)maxFID=pR.getFID();
    }

    for(protspace::MMResidue* resI:listInsertedRes)
    {
        maxFID++;
        resI->setFID(maxFID);
    }
}
