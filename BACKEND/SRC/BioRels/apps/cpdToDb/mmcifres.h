#ifndef MMCIFRES_H
#define MMCIFRES_H
#include <cstdint>
#include "headers/molecule/macromole.h"
#include "headers/statics/protpool.h"
class MMCifRes
{
protected:
    struct sminchi
       {
          protspace::StringPoolObj mType;
          protspace::StringPoolObj mProgram;
          protspace::StringPoolObj mProgram_version;
          protspace::StringPoolObj mDescriptor	  ;
          sminchi():mType(""),mProgram(""),mProgram_version(""),mDescriptor(""){}
       };
       ///
       /// \brief 3-Letter HET code
       ///
       std::string mHET;

       ///
       /// \brief List of possible data that are described in _chem_comp block
       ///
       std::map<std::string,std::string> mChemComp;

       std::map<std::string,double> mChemCompDbl;

       protspace::MacroMole& mMole;

       std::vector<protspace::MMAtom*> mAromAtom;


       std::vector<sminchi> mListSMI;

       static std::map<int,std::vector<std::string>> sListRules;

       /**
        * @brief loadFile
        * @param dir
        * @param fname
        * @param list
        * @throw 2320201  MMCifRes::loadFile              Unable to open rule file
        */
       static void loadFile(const std::string& dir,
                                  const std::string& fname,
                                  std::vector<std::string>& list) throw(ProtExcept);

       /**
        * @brief loadRules
        * @throw 2320101  MMCIFRes::loadRules             No C3TK_HOME Path set
        * @throw 2320201  MMCifRes::loadFile              Unable to open rule file
        */
       static void loadRules();
   public:

       /**
        * @brief MMCifRes
        * @param mole
        * @throw 2320101  MMCIFRes::loadRules             No C3TK_HOME Path set
        * @throw 2320201  MMCifRes::loadFile              Unable to open rule file
        */
       MMCifRes( protspace::MacroMole& mole);


       /**
        * @brief Assign the 3Letter Code to this mmcifres
        * @param HET 3 Letters code to be assigned to
        * @throw 351401    MacroMole::addChain     Bad Allocation
        * @throw 351502    MacroMole::addResidue   Bad allocation
        * @throw 351504   MacroMole::addResidue       Residue name empty
        */
       void setHET(const std::string& HET);

       ///
       /// \brief Add a new pair key-value to the ChemComp information
       /// \param fHead Key in the pair
       /// \param fValue Value of the pair
       ///
       void addChemComp(const std::string& fHead,
                        const std::string& fValue);

       void addChemCompDbl(const std::string& fHead,
                        const std::string& fValue);

       /**
        * @brief addAtom
        * @param x_v
        * @param y_v
        * @param z_v
        * @param isAromatic
        * @param fcharge
        * @param element
        * @param name
        * @throw 350102 MacroMole::addAtom Bad allocation
        * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
        * @throw 310102   MMAtom::setAtomicName   Atomic name not found
        */
       void addAtom(const double& x_v,
                    const double& y_v,
                    const double& z_v,
                    const bool& isAromatic,
                    const double& fcharge,
                    const std::string& element,
                    const std::string &name);

       /**
        * @brief addBond
        * @param atom1
        * @param atom2
        * @param order
        * @param arom
        * @param stereo
        * @param ordinal
        * @throw 320501     Residue::getAtom    Atom Not found
        * @throw 320502     Residue::getAtom    Atom Not found
        * @throw 350604   MacroMole::addBond          Both atoms are the same
        * @throw 030303 Bad allocation
        */
       void addBond(const std::string& atom1,
                    const std::string& atom2,
                    const std::string& order,
                    const bool& arom,
                    const bool& stereo,
                    const int&ordinal);

       void addSMINCHI(const  std::string& fType,
       const std::string& fProgram,
       const std::string& fProgram_version,
       const std::string& fDescriptor);
       std::string perceiveClass();
       bool perceiveAtom();


       /**
        * @brief getSMILES
        * @return
        * @throw 2320401  MMCifRes::GetSMILES             No SMILES FOUND
        */
       std::string getSMILES()const;


       /**
        * @brief getName
        * @return
        * @throw 2320301  MMCifRes::GetName               No name found
        */
       const std::string& getName() const;
       const std::string& getHET() const {return mHET;}
       std::string getReplaceBy() const;
       const uint16_t &getResidueType() const;
        protspace::MacroMole& getMolecule();
       void toHETListFile(std::ofstream& ofs) const;


       void loadIntExtMap();


};

#endif // MMCIFRES_H
